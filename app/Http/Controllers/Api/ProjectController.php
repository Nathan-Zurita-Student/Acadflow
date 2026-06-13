<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService,
        private NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = Project::where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn($q) => $q->where('users.id', $request->user()->id))
            ->with(['owner', 'members'])
            ->withCount('tasks')
            ->latest()
            ->get()
            ->map(fn(Project $p) => $this->projectResource($p, $request->user()->id));

        return response()->json($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();

        $project = $this->projectService->createProject($request->user(), $data);
        $project->load(['owner', 'members']);

        return response()->json($this->projectResource($project, $request->user()->id), 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $project->load(['owner', 'members', 'tags']);

        return response()->json($this->projectResource($project, $request->user()->id));
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validated();

        $project = $this->projectService->updateProject($project, $data);
        $project->load(['owner', 'members']);

        $this->projectService->logActivity($project, $request->user(), 'updated_project', 'Project', $project->id);

        return response()->json($this->projectResource($project, $request->user()->id));
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json(null, 204);
    }

    public function members(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $stats = $this->projectService->getMemberStats($project);

        return response()->json($stats);
    }

    public function addMember(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['nullable', 'in:leader,member'],
        ]);

        $this->projectService->addMember($project, $data['user_id'], $data['role'] ?? 'member');

        $newMember = User::find($data['user_id']);
        if ($newMember && $newMember->id !== $request->user()->id) {
            $roleLabel = ($data['role'] ?? 'member') === 'leader' ? 'líder' : 'membro';
            $this->notifications->notify(
                $newMember,
                'project_member_added',
                'Você foi adicionado a um projeto 🎓',
                "{$request->user()->name} adicionou você como {$roleLabel} no projeto \"{$project->name}\".",
                ['project_id' => $project->id],
            );
        }

        $this->projectService->broadcastMembersChanged($project);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json(['message' => 'Membro adicionado com sucesso.']);
    }

    public function removeMember(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('update', $project);

        if ($userId === $project->owner_id) {
            return response()->json(['message' => 'O dono do projeto não pode ser removido.'], 422);
        }

        // Captura os afetados ANTES de desvincular (inclui o membro removido)
        $affected = $this->projectService->affectedUserIds($project);

        $this->projectService->removeMember($project, $userId);

        $removed = User::find($userId);
        if ($removed) {
            $this->notifications->notify(
                $removed,
                'project_removed',
                'Você foi removido de um projeto',
                "{$request->user()->name} removeu você do projeto \"{$project->name}\".",
                ['project_id' => $project->id],
            );
        }

        $this->projectService->broadcastMembersChanged($project);
        $this->projectService->broadcastDashboardStaleForUsers($affected);

        return response()->json(null, 204);
    }

    public function leave(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $user = $request->user();

        if ($user->id === $project->owner_id) {
            return response()->json([
                'message' => 'O dono não pode sair do projeto. Transfira a propriedade ou exclua o projeto.',
            ], 422);
        }

        $affected = $this->projectService->affectedUserIds($project);

        $this->projectService->removeMember($project, $user->id);

        $this->projectService->broadcastMembersChanged($project);
        $this->projectService->broadcastDashboardStaleForUsers($affected);

        return response()->json(null, 204);
    }

    public function dashboard(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()->with('assignee')->get();
        $overdue = $tasks->filter(fn($t) => $t->isOverdue());

        $tasksByStatus = $tasks->groupBy('status')->map->count();
        $tasksByPriority = $tasks->groupBy('priority')->map->count();

        $recentActivity = $project->activityLogs()
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $memberStats = $this->projectService->getMemberStats($project);
        $riskLevel = $this->projectService->getRiskLevel($project);

        $weeklyData = $this->getWeeklyCompletions($project);

        return response()->json([
            'project' => $this->projectResource($project, $request->user()->id),
            'tasks_total' => $tasks->count(),
            'tasks_done' => $tasks->where('status', 'done')->count(),
            'tasks_overdue' => $overdue->count(),
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
            'recent_activity' => $recentActivity,
            'member_stats' => $memberStats,
            'risk_level' => $riskLevel,
            'weekly_completions' => $weeklyData,
        ]);
    }

    private function getWeeklyCompletions(Project $project): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = $project->tasks()
                ->where('status', 'done')
                ->whereDate('updated_at', $date)
                ->count();
            $data[] = ['date' => $date, 'count' => $count];
        }
        return $data;
    }

    private function projectResource(Project $project, int $userId): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'category' => $project->category,
            'status' => $project->status,
            'deadline' => $project->deadline?->toDateString(),
            'progress' => $project->progress,
            'owner' => $project->owner ? [
                'id' => $project->owner->id,
                'name' => $project->owner->name,
                'avatar' => $project->owner->avatar,
            ] : null,
            'members' => $project->members->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'avatar' => $m->avatar,
                'role' => $m->pivot->role,
            ]),
            'tasks_count' => $project->tasks_count ?? $project->tasks()->count(),
            'is_owner' => $project->owner_id === $userId,
            'created_at' => $project->created_at,
        ];
    }
}
