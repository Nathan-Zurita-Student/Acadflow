<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\Tasks\TaskDueSoonNotification;
use App\Support\SafeNotify;
use Illuminate\Console\Command;

/**
 * Envia um e-mail aos responsáveis por tarefas que vencem amanhã.
 * Agendado diariamente (ver routes/console.php).
 */
class NotifyDueTasks extends Command
{
    protected $signature = 'tasks:notify-due';

    protected $description = 'Envia e-mail aos responsáveis por tarefas que vencem amanhã.';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $tasks = Task::with([
            'project:id,name',
            'assignee:id,name,email,display_name',
            'assignees:id,name,email,display_name',
        ])
            ->whereDate('due_date', $tomorrow)
            ->whereNotIn('status', ['done'])
            ->get();

        // Agrupa por usuário (responsável único + múltiplos responsáveis).
        $byUser = [];
        foreach ($tasks as $task) {
            $recipients = collect([$task->assignee])->merge($task->assignees)->filter()->unique('id');
            foreach ($recipients as $user) {
                $byUser[$user->id] ??= ['user' => $user, 'tasks' => []];
                $byUser[$user->id]['tasks'][] = [
                    'title'   => $task->title,
                    'project' => $task->project?->name ?? '—',
                ];
            }
        }

        foreach ($byUser as $entry) {
            SafeNotify::attempt(
                fn () => $entry['user']->notify(new TaskDueSoonNotification($entry['tasks'])),
                'task_due_email',
            );
        }

        $this->info(count($byUser).' usuário(s) notificado(s) sobre '.$tasks->count().' tarefa(s) que vence(m) amanhã.');

        return self::SUCCESS;
    }
}
