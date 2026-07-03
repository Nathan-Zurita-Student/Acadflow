<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso de segurança: a senha da conta foi alterada. */
class PasswordChangedNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua senha foi alterada — AcadFlow')
            ->greeting('Olá!')
            ->line('A senha da sua conta AcadFlow acabou de ser alterada.')
            ->line('Por segurança, as demais sessões foram desconectadas.')
            ->line('Se não foi você, redefina sua senha imediatamente e contate o suporte.');
    }
}
