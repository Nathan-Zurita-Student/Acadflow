<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona "data de criação" à tabela de sessões para o gerenciamento de
 * sessões (requisito 8). O session handler do Laravel não escreve esta coluna,
 * então usamos DEFAULT CURRENT_TIMESTAMP para o banco preenchê-la na inserção.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->useCurrent()->after('last_activity');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
};
