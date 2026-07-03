<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Base para os códigos OTP (verificação de e-mail, recuperação de senha e
 * troca de e-mail). Concentra casts e helpers comuns; cada subclasse define
 * apenas a sua tabela e os campos preenchíveis.
 */
abstract class VerificationCode extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts'   => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasTooManyAttempts(int $max): bool
    {
        return $this->attempts >= $max;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
