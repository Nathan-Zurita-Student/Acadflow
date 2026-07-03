<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Grandfathering": marca como verificados os usuários que já existiam antes da
 * introdução da verificação de e-mail por código, evitando travar contas
 * legítimas. Novos cadastros passam normalmente pelo fluxo de verificação.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Sem rollback: não há como saber quais eram nulos anteriormente.
    }
};
