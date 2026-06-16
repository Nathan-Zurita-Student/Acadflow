<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Plano atual do usuário: free | pro | ultra
            $table->string('plan')->default('free')->after('role');
            // Situação da assinatura: active | overdue | canceled | inactive
            $table->string('plan_status')->default('inactive')->after('plan');
            // Quando o acesso ao plano pago expira (renovado a cada pagamento confirmado).
            $table->timestamp('plan_expires_at')->nullable()->after('plan_status');

            // IDs do lado do ASAAS para amarrar usuário <-> cobrança.
            $table->string('asaas_customer_id')->nullable()->after('plan_expires_at');
            $table->string('asaas_subscription_id')->nullable()->after('asaas_customer_id');

            // Documento usado na cobrança (ASAAS exige CPF/CNPJ para criar o cliente).
            $table->string('cpf_cnpj')->nullable()->after('asaas_subscription_id');

            // Contador de uso de IA no mês corrente (para o limite por plano).
            $table->unsignedInteger('ai_usage_count')->default(0)->after('cpf_cnpj');
            $table->string('ai_usage_period')->nullable()->after('ai_usage_count'); // ex: "2026-06"

            $table->index('asaas_subscription_id');
            $table->index('asaas_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['asaas_subscription_id']);
            $table->dropIndex(['asaas_customer_id']);
            $table->dropColumn([
                'plan',
                'plan_status',
                'plan_expires_at',
                'asaas_customer_id',
                'asaas_subscription_id',
                'cpf_cnpj',
                'ai_usage_count',
                'ai_usage_period',
            ]);
        });
    }
};
