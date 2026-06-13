<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\ProjectService;

class TaskObserver
{
    public function __construct(private ProjectService $projectService) {}

    public function created(Task $task): void
    {
        $this->clear($task);
    }

    public function updated(Task $task): void
    {
        $this->clear($task);
    }

    public function deleted(Task $task): void
    {
        $this->clear($task);
    }

    private function clear(Task $task): void
    {
        $project = $task->project()->with('members')->first();
        if ($project) {
            $this->projectService->clearDashboardCaches($project);
        }
    }
}
