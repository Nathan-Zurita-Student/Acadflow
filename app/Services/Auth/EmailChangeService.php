<?php

namespace App\Services\Auth;

use App\Events\Auth\EmailChanged;
use App\Models\EmailChangeCode;
use App\Models\User;
use App\Notifications\Auth\EmailChangeCodeNotification;
use App\Notifications\Auth\EmailChangedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class EmailChangeService
{
    public function __construct(private readonly VerificationCodeService $codes) {}

    /** Gera o código e envia para o NOVO e-mail (que ainda não é do usuário). */
    public function requestChange(User $user, string $newEmail): void
    {
        $code = $this->codes->issue(EmailChangeCode::class, $user, ['new_email' => $newEmail]);

        Notification::route('mail', $newEmail)
            ->notify(new EmailChangeCodeNotification($code));
    }

    /**
     * Confirma a troca: valida o código, aplica o novo e-mail e avisa o antigo.
     * Retorna o novo e-mail.
     */
    public function confirm(User $user, string $code): string
    {
        $record = $this->codes->validateCode(EmailChangeCode::class, $user, $code);
        $newEmail = $record->new_email;
        $oldEmail = $user->email;

        // Revalida a unicidade no momento da confirmação (evita corrida).
        $taken = User::where('email', $newEmail)->whereKeyNot($user->id)->exists();
        if ($taken) {
            $this->codes->consume($record);
            throw ValidationException::withMessages([
                'code' => ['Este e-mail já está em uso.'],
            ]);
        }

        $user->forceFill([
            'email'             => $newEmail,
            'email_verified_at' => now(), // verificado via código enviado ao novo e-mail
        ])->save();

        $this->codes->consume($record);

        Notification::route('mail', $oldEmail)
            ->notify(new EmailChangedNotification($newEmail));

        event(new EmailChanged($user, $oldEmail, $newEmail));

        return $newEmail;
    }
}
