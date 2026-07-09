<?php

namespace App\Notifications\Projects;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail enviado a um usuário convidado para um projeto.
 * Disparado por App\Http\Controllers\Api\ProjectInvitationController::store().
 */
class ProjectInvitationNotification extends Notification
{
    public function __construct(
        public readonly string $projectName,
        public readonly string $inviterName,
        public readonly string $role,
        public readonly string $token,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Convite para \"{$this->projectName}\" — AcadFlow")
            ->markdown('emails.project-invitation', [
                'name'    => $notifiable->display_name ?: $notifiable->name,
                'project' => $this->projectName,
                'inviter' => $this->inviterName,
                'role'    => $this->role === 'leader' ? 'Líder' : 'Membro',
                'url'     => rtrim(config('app.url'), '/') . '/invite/' . $this->token,
            ]);
    }
}
