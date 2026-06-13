<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado por tarefa — qualquer membro do projeto pode ouvir
Broadcast::channel('task.{taskId}', function ($user, int $taskId) {
    $task = Task::with('project.members')->find($taskId);
    if (! $task) return false;

    $project = $task->project;
    return $project->owner_id === $user->id
        || $project->members->contains('id', $user->id);
});

// Canal privado por projeto — board, tarefas e membros em tempo real
Broadcast::channel('project.{projectId}', function ($user, int $projectId) {
    $project = Project::with('members')->find($projectId);
    if (! $project) return false;

    return $project->owner_id === $user->id
        || $project->members->contains('id', $user->id);
});

// Canal de presença por tarefa — quem está "vendo a task" + digitação (whisper)
// Nome base sem prefixo: o cliente entra via echo.join('task-presence.'+id)
Broadcast::channel('task-presence.{taskId}', function ($user, int $taskId) {
    $task = Task::with('project.members')->find($taskId);
    if (! $task) return null;

    $project = $task->project;
    $allowed = $project->owner_id === $user->id
        || $project->members->contains('id', $user->id);

    if (! $allowed) return null;

    return [
        'id'     => $user->id,
        'name'   => $user->display_name ?? $user->name,
        'avatar' => $user->avatar,
    ];
});
