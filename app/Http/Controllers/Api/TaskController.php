<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentPosted;
use App\Http\Controllers\Controller;
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

        return response()->json($query->orderBy('position')->get()->map(fn($t) => $this->taskResource($t)));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['exists:users,id'],
            'status'       => ['nullable', 'in:backlog,pending,in_progress,review,done'],
            'priority'     => ['nullable', 'in:low,medium,high,urgent'],
            'due_date'     => ['nullable', 'date'],
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['exists:tags,id'],
        ]);

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

        $task->load(['assignee', 'assignees', 'tags', 'checklists']);

        return response()->json($this->taskResource($task), 201);
    }

    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);
        $task->load(['assignee', 'creator', 'tags', 'checklists', 'comments.user', 'attachments.uploader']);

        return response()->json($this->taskDetailResource($task));
    }

    public function update(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'title'          => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'assignee_ids'   => ['nullable', 'array'],
            'assignee_ids.*' => ['exists:users,id'],
            'status'         => ['sometimes', 'in:backlog,pending,in_progress,review,done'],
            'priority'       => ['sometimes', 'in:low,medium,high,urgent'],
            'due_date'       => ['nullable', 'date'],
            'tag_ids'        => ['nullable', 'array'],
            'tag_ids.*'      => ['exists:tags,id'],
        ]);

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

        $task->load(['assignee', 'assignees', 'tags', 'checklists']);

        return response()->json($this->taskResource($task));
    }

    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);
        $task->delete();
        $project->recalculateProgress();

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

        return response()->json(['message' => 'Tarefas reordenadas.']);
    }

    public function submitApproval(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $task->update(['approval_status' => 'pending', 'rejection_note' => null]);

        $this->projectService->logActivity($project, $request->user(), 'submitted_approval', 'Task', $task->id, ['title' => $task->title]);

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

        return response()->json(['approval_status' => 'rejected', 'rejection_note' => $task->rejection_note]);
    }

    public function logTime(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['seconds' => ['required', 'integer', 'min:1']]);

        $task->timeLogs()->create([
            'user_id' => $request->user()->id,
            'seconds' => $data['seconds'],
        ]);

        return response()->json([
            'time_seconds' => $task->timeLogs()->sum('seconds'),
        ]);
    }

    public function storeComment(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['content' => ['required', 'string']]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $comment->load('user');

        $this->projectService->logActivity($project, $request->user(), 'commented_task', 'Task', $task->id);

        // notificar assignees e criador (exceto quem comentou)
        $task->load('assignees');
        $notifyIds = collect([$task->created_by])
            ->merge($task->assignees->pluck('id'))
            ->unique()->filter(fn($id) => $id && $id !== $request->user()->id);
        foreach ($notifyIds as $uid) {
            $this->notifications->notify($uid, 'task_comment', 'Novo comentário na tarefa 💬',
                "{$request->user()->name} comentou em \"{$task->title}\"",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        $commentPayload = [
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user'       => ['id' => $comment->user->id, 'name' => $comment->user->name, 'avatar' => $comment->user->avatar],
            'created_at' => $comment->created_at,
        ];

        try {
            broadcast(new CommentPosted($task->id, $project->id, $commentPayload));
        } catch (\Throwable $e) {
            \Log::warning('CommentPosted broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'user' => ['id' => $comment->user->id, 'name' => $comment->user->name, 'avatar' => $comment->user->avatar],
            'created_at' => $comment->created_at,
        ], 201);
    }

    public function storeChecklist(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $maxPos = $task->checklists()->max('position') ?? -1;

        $item = $task->checklists()->create([
            'title' => $data['title'],
            'position' => $maxPos + 1,
        ]);

        return response()->json($item, 201);
    }

    public function updateChecklist(Request $request, Project $project, Task $task, int $checklistId): JsonResponse
    {
        $this->authorize('view', $project);

        $item = $task->checklists()->findOrFail($checklistId);
        $data = $request->validate(['completed' => ['required', 'boolean']]);
        $item->update($data);

        return response()->json($item);
    }

    public function destroyChecklist(Request $request, Project $project, Task $task, int $checklistId): JsonResponse
    {
        $this->authorize('view', $project);
        $task->checklists()->where('id', $checklistId)->delete();

        return response()->json(null, 204);
    }

    private function taskResource(Task $task): array
    {
        $assignees = $task->relationLoaded('assignees')
            ? $task->assignees->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar])
            : collect();

        return [
            'id'               => $task->id,
            'title'            => $task->title,
            'description'      => $task->description,
            'status'           => $task->status,
            'priority'         => $task->priority,
            'due_date'         => $task->due_date?->toDateString(),
            'position'         => $task->position,
            'is_overdue'       => $task->isOverdue(),
            'assignee'         => $task->assignee ? [
                'id'     => $task->assignee->id,
                'name'   => $task->assignee->name,
                'avatar' => $task->assignee->avatar,
            ] : null,
            'assignees'        => $assignees,
            'tags'             => $task->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]),
            'checklists_total' => $task->checklists->count(),
            'checklists_done'  => $task->checklists->where('completed', true)->count(),
            'time_seconds'     => $task->relationLoaded('timeLogs') ? $task->timeLogs->sum('seconds') : 0,
            'approval_status'  => $task->approval_status,
            'rejection_note'   => $task->rejection_note,
            'created_at'       => $task->created_at,
            'updated_at'       => $task->updated_at,
        ];
    }

    private function taskDetailResource(Task $task): array
    {
        return array_merge($this->taskResource($task), [
            'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
            'checklists' => $task->checklists->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'completed' => $c->completed,
                'position' => $c->position,
            ]),
            'comments' => $task->comments->map(fn($c) => [
                'id' => $c->id,
                'content' => $c->content,
                'user' => ['id' => $c->user->id, 'name' => $c->user->name, 'avatar' => $c->user->avatar],
                'created_at' => $c->created_at,
            ]),
            'attachments' => $task->attachments->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'mime_type' => $a->mime_type,
                'size' => $a->size,
                'url' => $a->url,
                'created_at' => $a->created_at,
            ]),
        ]);
    }
}
