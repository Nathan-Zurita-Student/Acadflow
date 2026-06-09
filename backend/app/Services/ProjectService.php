<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectService
{
    public function createProject(User $owner, array $data): Project
    {
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? 'planning',
            'deadline' => $data['deadline'] ?? null,
        ]);

        $project->members()->attach($owner->id, ['role' => 'leader']);

        return $project;
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project->update(array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => $data['status'] ?? null,
            'deadline' => $data['deadline'] ?? null,
        ], fn($v) => $v !== null));

        return $project->fresh();
    }

    public function addMember(Project $project, int $userId, string $role = 'member'): void
    {
        if (! $project->members()->where('user_id', $userId)->exists()) {
            $project->members()->attach($userId, ['role' => $role]);
        }
    }

    public function removeMember(Project $project, int $userId): void
    {
        $project->members()->detach($userId);
    }

    public function getRiskLevel(Project $project): string
    {
        $score = 0;

        $overdueTasks = $project->tasks()
            ->where('status', '!=', 'done')
            ->where('due_date', '<', now())
            ->count();

        $totalTasks = $project->tasks()->count();

        if ($overdueTasks > 0 && $totalTasks > 0) {
            $overdueRatio = $overdueTasks / $totalTasks;
            if ($overdueRatio > 0.5) {
                $score += 3;
            } elseif ($overdueRatio > 0.2) {
                $score += 2;
            } else {
                $score += 1;
            }
        }

        if ($project->deadline) {
            $daysLeft = now()->diffInDays($project->deadline, false);
            if ($daysLeft < 0) {
                $score += 3;
            } elseif ($daysLeft < 7) {
                $score += 2;
            } elseif ($daysLeft < 14) {
                $score += 1;
            }
        }

        if ($project->progress < 30 && $project->deadline) {
            $totalDays = $project->created_at->diffInDays($project->deadline);
            $elapsed = $project->created_at->diffInDays(now());
            if ($totalDays > 0 && ($elapsed / $totalDays) > 0.5) {
                $score += 2;
            }
        }

        return match(true) {
            $score >= 5 => 'high',
            $score >= 3 => 'medium',
            default => 'low',
        };
    }

    public function getMemberStats(Project $project): Collection
    {
        return $project->members->map(function (User $user) use ($project) {
            $tasks = $project->tasks()->where('assignee_id', $user->id);
            $total = $tasks->count();
            $done = (clone $tasks)->where('status', 'done')->count();
            $overdue = (clone $tasks)
                ->where('status', '!=', 'done')
                ->where('due_date', '<', now())
                ->count();

            $score = $this->calculateProductivityScore($total, $done, $overdue);

            return [
                'user' => $user,
                'total_tasks' => $total,
                'completed_tasks' => $done,
                'overdue_tasks' => $overdue,
                'participation' => $project->tasks()->count() > 0
                    ? round(($total / $project->tasks()->count()) * 100)
                    : 0,
                'score' => $score,
                'grade' => $this->scoreToGrade($score),
            ];
        });
    }

    private function calculateProductivityScore(int $total, int $done, int $overdue): int
    {
        if ($total === 0) return 50;
        $base = ($done / $total) * 100;
        $penalty = ($overdue / $total) * 30;
        return (int) max(0, min(100, $base - $penalty));
    }

    private function scoreToGrade(int $score): string
    {
        return match(true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 50 => 'C',
            default => 'D',
        };
    }

    public function logActivity(Project $project, User $user, string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $data = null): void
    {
        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'data' => $data,
        ]);
    }
}
