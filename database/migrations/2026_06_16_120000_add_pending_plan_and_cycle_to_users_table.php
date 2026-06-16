<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Plano para o qual o usuário está migrando (upgrade/downgrade/reativação).
            // Enquanto pendente, o usuário MANTÉM o plano atual (`plan`) até o novo
            // pagamento confirmar; só então `pending_plan` é promovido a `plan`.
            $table->string('pending_plan')->nullable()->after('plan_status');

            // Ciclo de cobrança da assinatura: monthly | annual. Usado para calcular
            // a próxima expiração (1 mês x 1 ano) e exibir na tela de planos.
            $table->string('plan_cycle')->default('monthly')->after('pending_plan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_plan', 'plan_cycle']);
        });
    }
};
