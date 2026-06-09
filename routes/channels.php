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
