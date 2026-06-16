<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AsaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(private readonly AsaasService $asaas)
    {
    }

    /**
     * Catálogo de planos + situação atual do usuário.
     * GET /api/plans
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'current' => [
                'plan'         => $user->effectivePlan(),
                'status'       => $user->plan_status,
                'cycle'        => $user->plan_cycle ?? 'monthly',
                'pending_plan' => $user->pending_plan,
                'expires_at'   => $user->plan_expires_at,
            ],
            'plans' => collect(config('plans.plans'))->map(fn ($plan, $key) => [
                'key'         => $key,
                'name'        => $plan['name'],
                'prices'      => $plan['prices'],
                'description' => $plan['description'],
                'limits'      => $plan['limits'],
            ])->values(),
        ]);
    }

    /**
     * Cria/inicia uma assinatura paga e devolve o link de pagamento.
     * Serve tanto para a 1ª assinatura quanto para upgrade/downgrade/troca de
     * ciclo e reativação de um plano cancelado.
     *
     * POST /api/subscriptions  { plan: 'pro'|'ultra', cycle: 'monthly'|'annual', cpf_cnpj: '...' }
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'plan'     => ['required', 'string', 'in:pro,ultra'],
            'cycle'    => ['required', 'string', 'in:monthly,annual'],
            // CPF/CNPJ só é obrigatório na 1ª assinatura; em upgrade/troca de
            // ciclo o cliente do ASAAS já existe e o documento é reaproveitado.
            'cpf_cnpj' => [$user->asaas_customer_id ? 'nullable' : 'required', 'string', 'min:11', 'max:18'],
        ]);

        $plan = config("plans.plans.{$data['plan']}");
        $value = $plan['prices'][$data['cycle']] ?? 0;

        if (! $plan || $value <= 0) {
            return response()->json(['message' => 'Plano inválido.'], 422);
        }

        try {
            $customerId = $this->asaas->ensureCustomer($user, $data['cpf_cnpj'] ?? '');

            // Troca de plano/ciclo: cancela a assinatura anterior no ASAAS para
            // não gerar cobrança dupla. (Reativação de um plano já cancelado
            // aponta para uma assinatura inativa — cancelar de novo é inofensivo.)
            if ($user->asaas_subscription_id) {
                $this->asaas->cancelSubscription($user->asaas_subscription_id);
            }

            $cycleLabel = $data['cycle'] === 'annual' ? ' (anual)' : '';
            $subscription = $this->asaas->createSubscription(
                customerId: $customerId,
                value: (float) $value,
                description: 'AcadFlow — Plano ' . $plan['name'] . $cycleLabel,
                cycle: $data['cycle'] === 'annual' ? 'YEARLY' : 'MONTHLY',
            );

            // Se o usuário JÁ tem acesso pago vigente (upgrade/troca/reativação),
            // mantemos o plano atual até o novo pagamento confirmar — sem cair
            // para o gratuito no meio do caminho. O novo plano fica "pendente".
            // Para quem está no gratuito, é o fluxo normal: pending até pagar.
            $keepsCurrentAccess = $user->effectivePlan() !== 'free';

            $user->update(array_merge(
                [
                    'asaas_subscription_id' => $subscription['id'],
                    'plan_cycle'            => $data['cycle'],
                ],
                $keepsCurrentAccess
                    ? ['pending_plan' => $data['plan']]
                    : ['plan' => $data['plan'], 'plan_status' => 'pending', 'pending_plan' => null],
            ));

            $invoiceUrl = $this->asaas->firstInvoiceUrl($subscription['id']);

            return response()->json([
                'message'     => 'Assinatura criada. Conclua o pagamento.',
                'invoice_url' => $invoiceUrl,
                'subscription_id' => $subscription['id'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao assinar plano', ['user' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Não foi possível iniciar a assinatura. Tente novamente.',
            ], 502);
        }
    }

    /**
     * Cancela a assinatura paga. O usuário mantém o plano até a data de expiração.
     * POST /api/subscriptions/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->asaas_subscription_id) {
            return response()->json(['message' => 'Você não possui assinatura ativa.'], 422);
        }

        $this->asaas->cancelSubscription($user->asaas_subscription_id);

        // Mantém o plano atual até expirar; abandona qualquer troca pendente.
        $user->update(['plan_status' => 'canceled', 'pending_plan' => null]);

        return response()->json(['message' => 'Assinatura cancelada.']);
    }

    /**
     * Webhook do ASAAS. Aqui é onde o plano realmente é liberado/bloqueado,
     * com base na confirmação de pagamento.
     * POST /api/webhooks/asaas  (rota pública, protegida por token)
     */
    public function webhook(Request $request): JsonResponse
    {
        // 1) Autenticação: o ASAAS reenvia o token que você cadastrou no painel.
        $expected = config('services.asaas.webhook_token');
        if ($expected && $request->header('asaas-access-token') !== $expected) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $event   = $request->input('event');
        $payment = $request->input('payment', []);

        $subscriptionId = $payment['subscription'] ?? null;
        $customerId     = $payment['customer'] ?? null;

        $user = User::query()
            ->when($subscriptionId, fn ($q) => $q->where('asaas_subscription_id', $subscriptionId))
            ->when(! $subscriptionId && $customerId, fn ($q) => $q->where('asaas_customer_id', $customerId))
            ->first();

        if (! $user) {
            // Não achou o usuário: respondemos 200 para o ASAAS não ficar reenviando.
            return response()->json(['message' => 'Ignorado.']);
        }

        switch ($event) {
            // Pagamento confirmado/recebido → libera o plano por 1 ciclo + tolerância.
            // Promove o plano pendente (upgrade/troca/reativação), se houver.
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $cycle = $user->plan_cycle ?? 'monthly';
                $expiresAt = $cycle === 'annual' ? now()->addYear() : now()->addMonth();

                $user->update([
                    'plan'            => $user->pending_plan ?: $user->plan,
                    'pending_plan'    => null,
                    'plan_status'     => 'active',
                    'plan_expires_at' => $expiresAt->addDays((int) config('plans.grace_days')),
                ]);
                break;

            // Venceu sem pagar → marca como atrasado (effectivePlan derruba após expirar).
            case 'PAYMENT_OVERDUE':
                $user->update(['plan_status' => 'overdue']);
                break;

            // Estorno/cobrança removida → volta para o gratuito.
            case 'PAYMENT_REFUNDED':
            case 'PAYMENT_DELETED':
                $user->update([
                    'plan'         => 'free',
                    'plan_status'  => 'inactive',
                    'pending_plan' => null,
                ]);
                break;
        }

        return response()->json(['message' => 'ok']);
    }
}
