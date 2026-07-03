<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Códigos (OTP) de troca de e-mail. Guarda o novo e-mail pretendido (ainda não
 * aplicado ao usuário) junto do hash do código enviado ao novo endereço.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_change_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('new_email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_codes');
    }
};
