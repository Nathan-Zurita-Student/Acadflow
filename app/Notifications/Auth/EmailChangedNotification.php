<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso enviado ao e-mail ANTIGO informando que o endereço foi alterado. */
class EmailChangedNotification extends Notification
{
    public function __construct(public readonly string $newEmail) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('O e-mail da sua conta foi alterado — AcadFlow')
            ->greeting('Olá!')
            ->line('O e-mail de acesso da sua conta AcadFlow foi alterado para: '.$this->newEmail)
            ->line('Se não foi você quem fez esta alteração, contate o suporte imediatamente.');
    }
}
