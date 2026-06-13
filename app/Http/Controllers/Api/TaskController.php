<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private ProjectService $projectService,
        private NotificationService $notifications,
    ) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $query = $project->tasks()->with(['assignee', 'assignees', 'tags', 'checklists', 'timeLogs']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->assignee_id) {
            $query->where('assignee_id', $request->assignee_id);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        return response()->json(TaskResource::collection($query->orderBy('position')->get()));
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validated();

        $assigneeIds = $data['assignee_ids'] ?? [];
        $maxPos = $project->tasks()->where('status', $data['status'] ?? 'backlog')->max('position') ?? -1;

        $task = $project->tasks()->create([
            'assignee_id' => $assigneeIds[0] ?? null,
            'created_by'  => $request->user()->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'backlog',
            'priority'    => $data['priority'] ?? 'medium',
            'due_date'    => $data['due_date'] ?? null,
            'position'    => $maxPos + 1,
        ]);

        if (! empty($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
        }

        if (! empty($data['tag_ids'])) {
            $task->tags()->sync($data['tag_ids']);
        }

        $project->recalculateProgress();
        $this->projectService->logActivity($project, $request->user(), 'created_task', 'Task', $task->id, ['title' => $task->title]);

        // notificar membros alocados
        foreach ($task->assignees as $member) {
            if ($member->id !== $request->user()->id) {
                $this->notifications->notify(
                    $member,
                    'task_assigned',
                    'Você foi alocado em uma tarefa',
                    "{$request->user()->name} alocou você na tarefa \"{$task->title}\" — {$project->name}",
                    ['project_id' => $project->id, 'task_id' => $task->id],
                );
            }
        }

        $task->load(['assignee', 'assignees', 'tags', 'checklists', 'timeLogs']);

        $resource = (new TaskResource($task))->resolve();
        $this->emitChange($project->id, 'created', $resource);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json($resource, 201);
    }

    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);
        $task->load(['assignee', 'assignees', 'creator', 'tags', 'checklists', 'comments.user', 'comments.reads', 'attachments.uploader']);

        return response()->json(new TaskResource($task));
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validated();

        $oldStatus   = $task->status;
        $oldPriority = $task->priority;
        $oldAssigneeIds = $task->assignees()->pluck('users.id')->all();

        $fields = [];
        foreach (['title', 'description', 'status', 'priority'] as $f) {
            if (array_key_exists($f, $data)) $fields[$f] = $data[$f];
        }
        if (array_key_exists('due_date', $data)) {
            $fields['due_date'] = $data['due_date'];
        }
        if (array_key_exists('assignee_ids', $data)) {
            $ids = $data['assignee_ids'] ?? [];
            $fields['assignee_id'] = $ids[0] ?? null;
            $task->assignees()->sync($ids);
        }

        if (! empty($fields)) $task->update($fields);

        if (array_key_exists('tag_ids', $data)) {
            $task->tags()->sync($data['tag_ids'] ?? []);
        }

        $project->recalculateProgress();
        $this->projectService->logActivity($project, $request->user(), 'updated_task', 'Task', $task->id, ['title' => $task->title]);

        $this->notifyTaskUpdate($request, $project, $task, $oldStatus, $oldPriority, $oldAssigneeIds);

        $task->load(['assignee', 'assignees', 'tags', 'checklists', 'timeLogs']);

        $resource = (new TaskResource($task))->resolve();
        $this->emitChange($project->id, 'updated', $resource);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json($resource);
    }

    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $taskId = $task->id;
        $status = $task->status;

        $task->delete();
        $project->recalculateProgress();

        $this->emitChange($project->id, 'deleted', ['id' => $taskId, 'status' => $status]);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json(null, 204);
    }

    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'tasks' => ['required', 'array'],
            'tasks.*.id' => ['required', 'exists:tasks,id'],
            'tasks.*.status' => ['required', 'in:backlog,pending,in_progress,review,done'],
            'tasks.*.position' => ['required', 'integer'],
        ]);

        foreach ($data['tasks'] as $item) {
            $project->tasks()->where('id', $item['id'])->update([
                'status' => $item['status'],
                'position' => $item['position'],
            ]);
        }

        $project->recalculateProgress();

        $this->emitChange($project->id, 'reordered', ['tasks' => $data['tasks']]);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json(['message' => 'Tarefas reordenadas.']);
    }

    public function submitApproval(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $task->update(['approval_status' => 'pending', 'rejection_note' => null]);

        $this->projectService->logActivity($project, $request->user(), 'submitted_approval', 'Task', $task->id, ['title' => $task->title]);

        // Notifica o dono e os líderes de que há uma tarefa aguardando aprovação
        $leaderIds = collect([$project->owner_id])
            ->merge($project->members()->wherePivot('role', 'leader')->pluck('users.id'))
            ->unique()->filter(fn($id) => $id && $id !== $request->user()->id);
        foreach ($leaderIds as $uid) {
            $this->notifications->notify($uid, 'task_approval_requested', 'Tarefa aguardando aprovação ⏳',
                "{$request->user()->name} enviou \"{$task->title}\" para sua aprovação.",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        $this->broadcastTaskUpdated($project, $task);

        return response()->json(['approval_status' => 'pending']);
    }

    public function approveTask(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $project);

        $isLeader = $project->owner_id === $request->user()->id
            || $project->members()->where('users.id', $request->user()->id)->wherePivot('role', 'leader')->exists();

        if (! $isLeader) {
            return response()->json(['message' => 'Apenas o líder pode aprovar tarefas.'], 403);
        }

        $task->update(['approval_status' => 'approved', 'rejection_note' => null]);

        $this->projectService->logActivity($project, $request->user(), 'approved_task', 'Task', $task->id, ['title' => $task->title]);

        // notificar criador e assignees
        $notifyIds = collect([$task->created_by])
            ->merge($task->assignees->pluck('id'))
            ->unique()->filter(fn($id) => $id && $id !== $request->user()->id);
        foreach ($notifyIds as $uid) {
            $this->notifications->notify($uid, 'task_approved', 'Tarefa aprovada! ✅',
                "Sua tarefa \"{$task->title}\" foi aprovada pelo líder.",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        $this->broadcastTaskUpdated($project, $task);

        return response()->json(['approval_status' => 'approved']);
    }

    public function rejectTask(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $project);

        $isLeader = $project->owner_id === $request->user()->id
            || $project->members()->where('users.id', $request->user()->id)->wherePivot('role', 'leader')->exists();

        if (! $isLeader) {
            return response()->json(['message' => 'Apenas o líder pode rejeitar tarefas.'], 403);
        }

        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $task->update(['approval_status' => 'rejected', 'rejection_note' => $data['note'] ?? null]);

        $this->projectService->logActivity($project, $request->user(), 'rejected_task', 'Task', $task->id, ['title' => $task->title]);

        $notifyIds = collect([$task->created_by])
            ->merge($task->assignees->pluck('id'))
            ->unique()->filter(fn($id) => $id && $id !== $request->user()->id);
        $noteMsg = $data['note'] ? " Motivo: {$data['note']}" : '';
        foreach ($notifyIds as $uid) {
            $this->notifications->notify($uid, 'task_rejected', 'Tarefa reprovada ❌',
                "Sua tarefa \"{$task->title}\" foi reprovada.{$noteMsg}",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        $this->broadcastTaskUpdated($project, $task);

        return response()->json(['approval_status' => 'rejected', 'rejection_note' => $task->rejection_note]);
    }

    private function broadcastTaskUpdated(Project $project, Task $task): void
    {
        $task->load(['assignee', 'assignees', 'tags', 'checklists', 'timeLogs']);
        $this->emitChange($project->id, 'updated', (new TaskResource($task))->resolve());
        $this->projectService->broadcastDashboardStale($project);
    }

    /** Broadcast à prova de falhas — uma queda do Reverb nunca quebra a ação principal. */
    private function emitChange(int $projectId, string $action, array $payload): void
    {
        try {
            broadcast(new TaskChanged($projectId, $action, $payload));
        } catch (\Throwable $e) {
            \Log::warning('TaskChanged broadcast failed: ' . $e->getMessage());
        }
    }

    private function notifyTaskUpdate(Request $request, Project $project, Task $task, ?string $oldStatus, ?string $oldPriority, array $oldAssigneeIds): void
    {
        $task->loadMissing('assignees');
        $actorId   = $request->user()->id;
        $actorName = $request->user()->name;
        $assigneeIds = $task->assignees->pluck('id')->all();

        // Novos responsáveis alocados nesta atualização
        $newAssigneeIds = array_values(array_diff($assigneeIds, $oldAssigneeIds));
        foreach ($newAssigneeIds as $uid) {
            if ($uid === $actorId) continue;
            $this->notifications->notify($uid, 'task_assigned', 'Você foi alocado em uma tarefa',
                "{$actorName} alocou você na tarefa \"{$task->title}\" — {$project->name}",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        // Demais destinatários (criador + responsáveis já existentes), exceto o autor da ação
        $recipientIds = collect([$task->created_by])
            ->merge($assigneeIds)
            ->reject(fn($id) => in_array($id, $newAssigneeIds, true))
            ->unique()->filter(fn($id) => $id && $id !== $actorId);

        if ($oldStatus !== $task->status) {
            foreach ($recipientIds as $uid) {
                $this->notifications->notify($uid, 'task_status', 'Status de tarefa alterado',
                    "{$actorName} moveu \"{$task->title}\" para {$this->statusLabel($task->status)}",
                    ['project_id' => $project->id, 'task_id' => $task->id]);
            }
        }

        if ($oldPriority !== $task->priority) {
            foreach ($recipientIds as $uid) {
                $this->notifications->notify($uid, 'task_priority', 'Prioridade de tarefa alterada',
                    "{$actorName} alterou a prioridade de \"{$task->title}\" para {$this->priorityLabel($task->priority)}",
                    ['project_id' => $project->id, 'task_id' => $task->id]);
            }
        }
    }

    private function statusLabel(string $s): string
    {
        return ['backlog' => 'Backlog', 'pending' => 'Pendente', 'in_progress' => 'Em andamento', 'review' => 'Revisão', 'done' => 'Concluída'][$s] ?? $s;
    }

    private function priorityLabel(string $p): string
    {
        return ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'][$p] ?? $p;
    }
}
