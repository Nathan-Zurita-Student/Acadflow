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
                'plan'        => $user->effectivePlan(),
                'status'      => $user->plan_status,
                'expires_at'  => $user->plan_expires_at,
            ],
            'plans' => collect(config('plans.plans'))->map(fn ($plan, $key) => [
                'key'         => $key,
                'name'        => $plan['name'],
                'price'       => $plan['price'],
                'description' => $plan['description'],
                'limits'      => $plan['limits'],
            ])->values(),
        ]);
    }

    /**
     * Cria/inicia uma assinatura paga e devolve o link de pagamento.
     * POST /api/subscriptions  { plan: 'pro'|'ultra', cpf_cnpj: '...' }
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan'     => ['required', 'string', 'in:pro,ultra'],
            'cpf_cnpj' => ['required', 'string', 'min:11', 'max:18'],
        ]);

        $user = $request->user();
        $plan = config("plans.plans.{$data['plan']}");

        if (! $plan || ($plan['price'] ?? 0) <= 0) {
            return response()->json(['message' => 'Plano inválido.'], 422);
        }

        try {
            $customerId = $this->asaas->ensureCustomer($user, $data['cpf_cnpj']);

            $subscription = $this->asaas->createSubscription(
                customerId: $customerId,
                value: (float) $plan['price'],
                description: 'AcadFlow — Plano ' . $plan['name'],
            );

            // Guardamos a intenção. O plano só vira "active" quando o webhook
            // confirmar o pagamento (PAYMENT_CONFIRMED / PAYMENT_RECEIVED).
            $user->update([
                'plan'                  => $data['plan'],
                'plan_status'           => 'pending',
                'asaas_subscription_id' => $subscription['id'],
            ]);

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

        $user->update(['plan_status' => 'canceled']);

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
            // Pagamento confirmado/recebido → libera o plano por ~1 mês + tolerância.
            case 'PAYMENT_CONFIRMED':
            case 'PAYMENT_RECEIVED':
                $user->update([
                    'plan_status'     => 'active',
                    'plan_expires_at' => now()->addMonth()->addDays((int) config('plans.grace_days')),
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
                    'plan'        => 'free',
                    'plan_status' => 'inactive',
                ]);
                break;
        }

        return response()->json(['message' => 'ok']);
    }
}
