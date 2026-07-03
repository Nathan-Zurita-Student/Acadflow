<?php

namespace App\Services\Auth;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\Auth\PasswordResetCodeNotification;
use App\Support\SafeNotify;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(
        private readonly VerificationCodeService $codes,
        private readonly SessionService $sessions,
    ) {}

    /**
     * Envia um código de recuperação. Sempre "silencioso": se o e-mail não
     * existir, nada acontece — o controller responde igual (não enumera contas).
     */
    public function sendCode(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $code = $this->codes->issue(PasswordResetCode::class, $user);

        SafeNotify::attempt(
            fn () => $user->notify(new PasswordResetCodeNotification($code)),
            'password_reset',
        );
    }

    /**
     * Redefine a senha após validar o código. Encerra TODAS as sessões e revoga
     * tokens do usuário. Falhas usam mensagem genérica (sem enumeração).
     */
    public function reset(string $email, string $code, string $password): void
    {
        $user = User::where('email', $email)->first();

        // Mensagem idêntica à de "sem código ativo" (VerificationCodeService),
        // para não permitir enumeração de e-mails cadastrados via este endpoint.
        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Código expirado ou inexistente. Solicite um novo.'],
            ]);
        }

        $record = $this->codes->validateCode(PasswordResetCode::class, $user, $code);

        $user->forceFill(['password' => $password])->save(); // cast 'hashed'
        $this->codes->consume($record);

        // Segurança: derruba qualquer sessão/token existente após reset.
        $this->sessions->destroyAll($user);
        $user->tokens()->delete();

        event(new PasswordReset($user));
    }
}
