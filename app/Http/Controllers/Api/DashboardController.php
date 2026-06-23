<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cache dashboard stats for 5 minutes — invalidated by TaskObserver on changes
        $data = Cache::remember("dashboard:{$user->id}", 300, function () use ($user) {
            return $this->buildDashboard($user);
        });

        return response()->json($data);
    }

    private function buildDashboard($user): array
    {
        $projectIds = Project::where('owner_id', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $projects = Project::whereIn('id', $projectIds)
            ->with(['owner', 'members'])
            ->withCount('tasks')
            ->get();

        // Eager load all tasks in one query instead of N queries inside foreach
        $allTasks = \App\Models\Task::whereIn('project_id', $projectIds)->get();
        $totalTasks   = $allTasks->count();
        $doneTasks    = $allTasks->where('status', 'done')->count();
        $overdueTasks = $allTasks->filter(fn($t) => $t->isOverdue())->count();

        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = \App\Models\Task::whereIn('project_id', $projectIds)
                ->where('status', 'done')
                ->whereDate('updated_at', $date)
                ->count();
            $weeklyData[] = ['date' => $date, 'count' => $count];
        }

        $recentActivity = \App\Models\ActivityLog::whereIn('project_id', $projectIds)
            ->with(['user', 'project'])
            ->latest()
            ->take(15)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'user' => $a->user ? ['name' => $a->user->name, 'avatar' => $a->user->avatar] : null,
                'project' => $a->project ? ['id' => $a->project->id, 'name' => $a->project->name] : null,
                'data' => $a->data,
                'created_at' => $a->created_at,
            ]);

        $projectsWithRisk = $projects->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'status' => $p->status,
            'progress' => $p->progress,
            'deadline' => $p->deadline?->toDateString(),
            'tasks_count' => $p->tasks_count,
            'risk_level' => $this->projectService->getRiskLevel($p),
        ]);

        // Próximas entregas do usuário (tarefas com prazo nos próx. 7 dias)
        $upcoming = \App\Models\Task::with('project:id,name')
            ->assignedTo($user->id)
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'status'     => $t->status,
                'priority'   => $t->priority,
                'due_date'   => $t->due_date?->toDateString(),
                'is_overdue' => $t->isOverdue(),
                'project'    => ['id' => $t->project->id, 'name' => $t->project->name],
            ]);

        return [
            'stats' => [
                'total_projects'  => $projects->count(),
                'total_tasks'     => $totalTasks,
                'done_tasks'      => $doneTasks,
                'overdue_tasks'   => $overdueTasks,
                'completion_rate' => $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0,
            ],
            'projects'           => $projectsWithRisk,
            'weekly_completions' => $weeklyData,
            'recent_activity'    => $recentActivity,
            'upcoming'           => $upcoming,
        ];
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $users = \App\Models\User::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('email', 'like', '%' . $request->q . '%')
            ->take(10)
            ->get(['id', 'name', 'email', 'avatar']);

        return response()->json($users);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = \App\Models\Task::with(['project:id,name', 'tags:id,name,color'])
            ->assignedTo($user->id)
            ->orderByRaw("CASE WHEN due_date IS NULL THEN 2 WHEN due_date < NOW() THEN 0 ELSE 1 END")
            ->orderBy('due_date', 'asc')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->limit(200)
            ->get()
            ->map(fn($t) => [
                'id'              => $t->id,
                'title'           => $t->title,
                'status'          => $t->status,
                'priority'        => $t->priority,
                'due_date'        => $t->due_date?->toDateString(),
                'is_overdue'      => $t->isOverdue(),
                'approval_status' => $t->approval_status,
                'project'         => ['id' => $t->project->id, 'name' => $t->project->name],
                'tags'            => $t->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color]),
            ]);

        return response()->json($tasks);
    }

    /**
     * Tarefas de TODOS os projetos do usuário (dono ou membro) cujo intervalo
     * [início, entrega] cruza a janela [from, to]. Alimenta a visão de Calendário.
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();

        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->subWeek()->toDateString();
        $to   = $request->date('to')?->toDateString()   ?? now()->endOfMonth()->addWeek()->toDateString();

        $projectIds = Project::where('owner_id', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $tasks = Task::with(['project:id,name', 'assignee:id,name,avatar', 'assignees:id,name,avatar'])
            ->whereIn('project_id', $projectIds)
            // Sobreposição de intervalos: usa due_date quando não há start_date e
            // vice-versa. Se ambos forem nulos, o COALESCE vira NULL e a tarefa fica de fora.
            ->where(function ($q) use ($from, $to) {
                $q->whereRaw('COALESCE(start_date, due_date) <= ?', [$to])
                  ->whereRaw('COALESCE(due_date, start_date) >= ?', [$from]);
            })
            ->orderBy('due_date')
            ->limit(500)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'status'     => $t->status,
                'priority'   => $t->priority,
                'start_date' => $t->start_date?->toDateString(),
                'due_date'   => $t->due_date?->toDateString(),
                'is_overdue' => $t->isOverdue(),
                'project'    => ['id' => $t->project->id, 'name' => $t->project->name],
                'assignee'   => $t->assignee
                    ? ['id' => $t->assignee->id, 'name' => $t->assignee->name, 'avatar' => $t->assignee->avatar]
                    : null,
                'assignees'  => $t->assignees
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar])
                    ->values(),
            ]);

        return response()->json($tasks);
    }
}
