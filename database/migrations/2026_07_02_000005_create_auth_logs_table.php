<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha de auditoria dedicada a eventos de autenticação/segurança
 * (cadastro, login, falha de login, logout, troca de senha/e-mail,
 * verificação, recuperação, exclusão de conta, cancelamento de assinatura).
 * Tabela separada da activity_logs (finalidades distintas — requisito 11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['user_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
