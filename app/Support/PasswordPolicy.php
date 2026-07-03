<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Política única de força de senha, reutilizada no cadastro, redefinição e
 * troca de senha (DRY). Exige tamanho mínimo, letras maiúsculas/minúsculas,
 * números e símbolos, e recusa senhas vazadas em bases públicas (HIBP).
 */
final class PasswordPolicy
{
    /** Regras para um campo de nova senha com confirmação (`*_confirmation`). */
    public static function rules(): array
    {
        return ['required', 'string', 'confirmed', self::strength()];
    }

    public static function strength(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
    }
}
