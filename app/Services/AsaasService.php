<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente fino para a API do ASAAS.
 *
 * Responsável apenas por falar HTTP com o ASAAS (criar cliente, criar/cancelar
 * assinatura e ler cobranças). Nenhuma regra de negócio mora aqui — isso fica
 * no SubscriptionController.
 *
 * Docs: https://docs.asaas.com/reference
 */
class AsaasService
{
    private function http(): PendingRequest
    {
        $key = config('services.asaas.key');

        if (empty($key)) {
            throw new RuntimeException('ASAAS_API_KEY não configurada no .env.');
        }

        return Http::baseUrl(rtrim(config('services.asaas.base_url'), '/'))
            ->withHeaders([
                'access_token' => $key,
                'Content-Type' => 'application/json',
            ])
            ->acceptJson()
            ->timeout(20);
    }

    /**
     * Garante que o usuário tenha um "customer" no ASAAS, criando se necessário.
     * Retorna o ID do customer (ex: "cus_000005219613").
     */
    public function ensureCustomer(User $user, string $cpfCnpj): string
    {
        if ($user->asaas_customer_id) {
            return $user->asaas_customer_id;
        }

        $response = $this->http()->post('/customers', [
            'name'         => $user->name,
            'email'        => $user->email,
            'cpfCnpj'      => preg_replace('/\D/', '', $cpfCnpj),
            'externalReference' => (string) $user->id,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao criar cliente no ASAAS: ' . $response->body());
        }

        $customerId = $response->json('id');

        $user->update([
            'asaas_customer_id' => $customerId,
            'cpf_cnpj'          => preg_replace('/\D/', '', $cpfCnpj),
        ]);

        return $customerId;
    }

    /**
     * Cria uma assinatura recorrente mensal.
     *
     * billingType = UNDEFINED deixa o cliente escolher Pix, cartão OU boleto
     * na própria página de pagamento do ASAAS.
     *
     * @return array{id:string, ...} dados da assinatura
     */
    public function createSubscription(string $customerId, float $value, string $description): array
    {
        $response = $this->http()->post('/subscriptions', [
            'customer'    => $customerId,
            'billingType' => 'UNDEFINED', // cliente escolhe Pix/cartão/boleto
            'value'       => $value,
            'nextDueDate' => now()->format('Y-m-d'),
            'cycle'       => 'MONTHLY',
            'description' => $description,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao criar assinatura no ASAAS: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Retorna a URL da fatura (página de pagamento) da primeira cobrança
     * gerada pela assinatura — é pra lá que mandamos o usuário pagar.
     */
    public function firstInvoiceUrl(string $subscriptionId): ?string
    {
        $response = $this->http()->get("/subscriptions/{$subscriptionId}/payments");

        if ($response->failed()) {
            return null;
        }

        $payments = $response->json('data', []);

        return $payments[0]['invoiceUrl'] ?? null;
    }

    /** Cancela uma assinatura no ASAAS. */
    public function cancelSubscription(string $subscriptionId): bool
    {
        $response = $this->http()->delete("/subscriptions/{$subscriptionId}");

        return $response->successful();
    }

    /** Busca os dados de uma cobrança específica. */
    public function getPayment(string $paymentId): ?array
    {
        $response = $this->http()->get("/payments/{$paymentId}");

        return $response->successful() ? $response->json() : null;
    }
}
