<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentPosted;
use App\Events\CommentStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\CommentRead;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\NotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskCommentController extends Controller
{
    public function __construct(
        private ProjectService $projectService,
        private NotificationService $notifications,
    ) {}

    public function store(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['content' => ['required', 'string']]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $comment->load('user');

        $this->projectService->logActivity($project, $request->user(), 'commented_task', 'Task', $task->id);

        $task->load('assignees');
        $notifyIds = collect([$task->created_by])
            ->merge($task->assignees->pluck('id'))
            ->unique()->filter(fn($id) => $id && $id !== $request->user()->id);
        foreach ($notifyIds as $uid) {
            $this->notifications->notify($uid, 'task_comment', 'Novo comentário na tarefa 💬',
                "{$request->user()->name} comentou em \"{$task->title}\"",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }

        // Menções @nome — notifica membros do projeto ainda não avisados
        $this->notifyMentions($request, $project, $task, $data['content'], $notifyIds);

        $payload = [
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user'       => ['id' => $comment->user->id, 'name' => $comment->user->name, 'avatar' => $comment->user->avatar],
            'created_at' => $comment->created_at,
            'status'     => 'sent',
        ];

        try {
            broadcast(new CommentPosted($task->id, $project->id, $payload));
        } catch (\Throwable $e) {
            \Log::warning('CommentPosted broadcast failed: ' . $e->getMessage());
        }

        return response()->json($payload, 201);
    }

    /** Marca como ENTREGUES (no navegador) todos os comentários do usuário atual. */
    public function markDelivered(Request $request, Project $project, Task $task): JsonResponse
    {
        return $this->ackComments($request, $project, $task, read: false);
    }

    /** Marca como LIDOS (e entregues) todos os comentários do usuário atual. */
    public function markRead(Request $request, Project $project, Task $task): JsonResponse
    {
        return $this->ackComments($request, $project, $task, read: true);
    }

    private function ackComments(Request $request, Project $project, Task $task, bool $read): JsonResponse
    {
        $this->authorize('view', $project);

        $userId = $request->user()->id;
        $linkedIds = $this->linkedIds($task);

        // O usuário precisa estar vinculado à task para que seu "visto" conte
        if (! $linkedIds->contains($userId)) {
            return response()->json(['ok' => true]);
        }

        $task->load('comments');
        $now = now();
        $flipped = []; // [commentId => status]

        foreach ($task->comments as $comment) {
            if ($comment->user_id === $userId) continue; // não é destinatário do próprio comentário

            $row = CommentRead::firstOrNew(['comment_id' => $comment->id, 'user_id' => $userId]);
            $wasDelivered = $row->delivered_at !== null;
            $wasRead      = $row->read_at !== null;

            if (! $row->delivered_at) $row->delivered_at = $now;
            if ($read && ! $row->read_at) $row->read_at = $now;
            if (! $row->exists || $row->isDirty()) $row->save();

            // Só recomputa/transmite se algo mudou para este usuário
            if ((! $wasDelivered) || ($read && ! $wasRead)) {
                $status = $this->aggregateStatus($comment, $linkedIds);
                if (($status === 'read' && ! $wasRead) || ($status === 'delivered' && ! $wasDelivered)) {
                    $flipped[$comment->id] = $status;
                }
            }
        }

        foreach ($flipped as $commentId => $status) {
            try {
                broadcast(new CommentStatusUpdated($task->id, $commentId, $status));
            } catch (\Throwable $e) {
                \Log::warning('CommentStatusUpdated broadcast failed: ' . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    /** Usuários "vinculados" à task: responsáveis + criador. */
    private function linkedIds(Task $task): Collection
    {
        $task->loadMissing('assignees');

        return $task->assignees->pluck('id')
            ->push($task->created_by)
            ->filter()
            ->unique()
            ->values();
    }

    /** Status agregado de um comentário: sent | delivered | read. */
    private function aggregateStatus(TaskComment $comment, Collection $linkedIds): string
    {
        $recipients = $linkedIds->reject(fn($id) => $id === $comment->user_id)->values();
        if ($recipients->isEmpty()) return 'sent';

        $reads = CommentRead::where('comment_id', $comment->id)
            ->whereIn('user_id', $recipients)
            ->get();

        $readAll = $recipients->every(fn($rid) => optional($reads->firstWhere('user_id', $rid))->read_at !== null);
        if ($readAll) return 'read';

        $deliveredAll = $recipients->every(fn($rid) => optional($reads->firstWhere('user_id', $rid))->delivered_at !== null);
        if ($deliveredAll) return 'delivered';

        return 'sent';
    }

    private function notifyMentions(Request $request, Project $project, Task $task, string $content, Collection $alreadyNotified): void
    {
        preg_match_all('/@([\p{L}]+)/u', $content, $matches);
        if (empty($matches[1])) return;

        $names = collect($matches[1])->map(fn($n) => mb_strtolower($n));
        $project->loadMissing('members');

        foreach ($project->members as $member) {
            $first = mb_strtolower(explode(' ', $member->name)[0] ?? '');
            if (! $names->contains($first)) continue;
            if ($member->id === $request->user()->id) continue;
            if ($alreadyNotified->contains($member->id)) continue;

            $this->notifications->notify($member->id, 'task_mention', 'Você foi mencionado 💬',
                "{$request->user()->name} mencionou você em \"{$task->title}\"",
                ['project_id' => $project->id, 'task_id' => $task->id]);
        }
    }
}
