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
        ]);
    }
}
