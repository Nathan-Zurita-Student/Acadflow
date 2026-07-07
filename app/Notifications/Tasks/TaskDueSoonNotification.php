<?php

namespace App\Notifications\Tasks;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lembrete de tarefas que vencem amanhã (agrupadas por usuário).
 * Disparado pelo comando `tasks:notify-due`.
 */
class TaskDueSoonNotification extends Notification
{
    /** @param array<int, array{title: string, project: string}> $tasks */
    public function __construct(public readonly array $tasks) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->tasks);

        return (new MailMessage)
            ->subject($count === 1 ? 'Uma tarefa vence amanhã — AcadFlow' : "{$count} tarefas vencem amanhã — AcadFlow")
            ->markdown('emails.task-due', [
                'name'  => $notifiable->display_name ?: $notifiable->name,
                'tasks' => $this->tasks,
                'url'   => rtrim((string) config('app.url'), '/').'/my-tasks',
            ]);
    }
}
