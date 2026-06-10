<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskObserver
{
    public function created(Task $task): void
    {
        $this->clearProjectDashboardCaches($task);
    }

    public function updated(Task $task): void
    {
        $this->clearProjectDashboardCaches($task);
    }

    public function deleted(Task $task): void
    {
        $this->clearProjectDashboardCaches($task);
    }

    private function clearProjectDashboardCaches(Task $task): void
    {
        // Clear dashboard cache for all members of this task's project
        $project = $task->project()->with('members')->first();
        if (! $project) return;

        $memberIds = $project->members->pluck('id');
        $ownerIds  = collect([$project->owner_id]);
        $allIds    = $memberIds->merge($ownerIds)->unique();

        foreach ($allIds as $userId) {
            Cache::forget("dashboard:{$userId}");
        }
    }
}
