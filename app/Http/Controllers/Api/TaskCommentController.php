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

        $payload = [
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user'       => ['id' => $comment->user->id, 'name' => $comment->user->name, 'avatar' => $comment->user->avatar],
            'created_at' => $comment->created_at,
        ];

        try {
            broadcast(new CommentPosted($task->id, $project->id, $payload));
        } catch (\Throwable $e) {
            \Log::warning('CommentPosted broadcast failed: ' . $e->getMessage());
        }

        return response()->json($payload, 201);
    }
}
