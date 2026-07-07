<?php

namespace App\Notifications\Reports;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Resumo semanal do usuário. Disparado pelo comando `reports:weekly`.
 */
class WeeklyReportNotification extends Notification
{
    /** @param array{completed: int, pending: int, overdue: int, upcoming: int} $stats */
    public function __construct(public readonly array $stats) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu resumo da semana — AcadFlow')
            ->markdown('emails.weekly-report', [
                'name'  => $notifiable->display_name ?: $notifiable->name,
                'stats' => $this->stats,
                'url'   => rtrim((string) config('app.url'), '/'),
            ]);
    }
}
