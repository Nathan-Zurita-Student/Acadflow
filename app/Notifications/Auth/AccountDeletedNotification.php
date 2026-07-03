<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmação de que a conta foi excluída (soft delete + carência). */
class AccountDeletedNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua conta foi excluída — AcadFlow')
            ->greeting('Até logo!')
            ->line('Sua conta AcadFlow foi excluída conforme solicitado.')
            ->line('Qualquer assinatura ativa foi cancelada e não haverá cobranças futuras.')
            ->line('Sentiremos sua falta. Se mudou de ideia, entre em contato com o suporte.');
    }
}
