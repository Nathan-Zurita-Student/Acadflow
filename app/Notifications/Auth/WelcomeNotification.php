<?php

namespace App\Notifications\Auth;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail de boas-vindas, enviado quando o usuário confirma o e-mail
 * (conta ativada). Ver App\Services\Auth\EmailVerificationService::verify().
 */
class WelcomeNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bem-vindo(a) ao AcadFlow! 🎓')
            ->markdown('emails.welcome', [
                'name' => $notifiable->display_name ?: $notifiable->name,
                'url'  => config('app.url'),
            ]);
    }
}
