<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $projectIds = Project::where('owner_id', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $projects = Project::whereIn('id', $projectIds)
            ->with(['owner', 'members'])
            ->withCount('tasks')
            ->get();

        $totalTasks = 0;
        $doneTasks = 0;
        $overdueTasks = 0;

        foreach ($projects as $project) {
            $tasks = $project->tasks()->get();
            $totalTasks += $tasks->count();
            $doneTasks += $tasks->where('status', 'done')->count();
            $overdueTasks += $tasks->filter(fn($t) => $t->isOverdue())->count();
        }

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
            ->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                  ->orWhereHas('assignees', fn($q2) => $q2->where('users.id', $user->id));
            })
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

        return response()->json([
            'stats' => [
                'total_projects' => $projects->count(),
                'total_tasks' => $totalTasks,
                'done_tasks' => $doneTasks,
                'overdue_tasks' => $overdueTasks,
                'completion_rate' => $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0,
            ],
            'projects' => $projectsWithRisk,
            'weekly_completions' => $weeklyData,
            'recent_activity' => $recentActivity,
            'upcoming' => $upcoming,
        ]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = \App\Models\Task::with(['project:id,name', 'tags:id,name,color'])
            ->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                  ->orWhereHas('assignees', fn($q2) => $q2->where('users.id', $user->id));
            })
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
}
