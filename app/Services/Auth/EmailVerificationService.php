<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\Auth\EmailVerificationCodeNotification;
use App\Support\SafeNotify;
use Illuminate\Auth\Events\Verified;

class EmailVerificationService
{
    public function __construct(private readonly VerificationCodeService $codes) {}

    /** Gera e envia um novo código de verificação para o e-mail do usuário. */
    public function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = $this->codes->issue(EmailVerificationCode::class, $user);

        SafeNotify::attempt(
            fn () => $user->notify(new EmailVerificationCodeNotification($code)),
            'email_verification',
        );
    }

    /**
     * Valida o código e marca o e-mail como verificado.
     * Lança ValidationException (campo `code`) em caso de falha.
     */
    public function verify(User $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $record = $this->codes->validateCode(EmailVerificationCode::class, $user, $code);

        $user->markEmailAsVerified();
        $this->codes->consume($record);

        event(new Verified($user));
    }
}
