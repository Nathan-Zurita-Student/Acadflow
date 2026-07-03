<?php

namespace App\Notifications\Auth;

use App\Services\Auth\VerificationCodeService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base para os e-mails que entregam um código OTP de 6 dígitos.
 * Enviadas de forma síncrona (entrega imediata do código ao usuário).
 */
abstract class CodeNotification extends Notification
{
    public function __construct(public readonly string $code) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine())
            ->greeting('Olá!')
            ->line($this->intro())
            ->line('**Seu código: '.$this->code.'**')
            ->line('Este código expira em '.VerificationCodeService::EXPIRY_MINUTES.' minutos.')
            ->line($this->outro());
    }

    abstract protected function subjectLine(): string;

    abstract protected function intro(): string;

    protected function outro(): string
    {
        return 'Se você não solicitou isto, ignore este e-mail com segurança.';
    }
}
