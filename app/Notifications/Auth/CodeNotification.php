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
            ->markdown('emails.code', [
                'heading' => $this->heading(),
                'intro'   => $this->intro(),
                'code'    => $this->code,
                'minutes' => VerificationCodeService::EXPIRY_MINUTES,
                'outro'   => $this->outro(),
            ]);
    }

    abstract protected function subjectLine(): string;

    abstract protected function intro(): string;

    /** Título grande exibido no corpo do e-mail (sobrescrevível). */
    protected function heading(): string
    {
        return 'Seu código de verificação';
    }

    protected function outro(): string
    {
        return 'Se você não solicitou isto, ignore este e-mail com segurança.';
    }
}
