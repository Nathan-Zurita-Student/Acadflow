<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Reports\WeeklyReportNotification;
use App\Support\SafeNotify;
use Illuminate\Console\Command;

/**
 * Envia o resumo semanal por e-mail aos usuários com atividade.
 * Agendado semanalmente (ver routes/console.php).
 */
class SendWeeklyReports extends Command
{
    protected $signature = 'reports:weekly';

    protected $description = 'Envia o resumo semanal por e-mail aos usuários ativos.';

    public function handle(): int
    {
        $sent        = 0;
        $weekAgo     = now()->subDays(7);
        $today       = now()->toDateString();
        $inSevenDays = now()->addDays(7)->toDateString();

        User::whereNotNull('email_verified_at')->chunkById(200, function ($users) use (&$sent, $weekAgo, $today, $inSevenDays) {
            foreach ($users as $user) {
                $projectIds = Project::where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($q) => $q->where('users.id', $user->id))
                    ->pluck('id');

                if ($projectIds->isEmpty()) {
                    continue;
                }

                $assigned = fn () => Task::assignedTo($user->id);

                $completed = $assigned()->where('status', 'done')->where('updated_at', '>=', $weekAgo)->count();
                $pending   = $assigned()->whereNotIn('status', ['done'])->count();
                $overdue   = $assigned()->whereNotIn('status', ['done'])->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count();
                $upcoming  = $assigned()->whereNotIn('status', ['done'])->whereNotNull('due_date')->whereBetween('due_date', [$today, $inSevenDays])->count();

                // Nada relevante → não envia (evita ruído).
                if (($completed + $pending + $overdue + $upcoming) === 0) {
                    continue;
                }

                SafeNotify::attempt(
                    fn () => $user->notify(new WeeklyReportNotification(compact('completed', 'pending', 'overdue', 'upcoming'))),
                    'weekly_report_email',
                );
                $sent++;
            }
        });

        $this->info("{$sent} relatório(s) semanal(is) enviado(s).");

        return self::SUCCESS;
    }
}
