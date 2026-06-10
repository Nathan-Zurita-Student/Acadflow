<?php

use App\Models\Project;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\ProjectService;

test('getRiskLevel returns low for a new project with no tasks', function () {
    $service = new ProjectService();

    $owner = User::factory()->create();
    $project = Project::factory()->create([
        'owner_id' => $owner->id,
        'deadline' => now()->addMonths(3),
    ]);

    expect($service->getRiskLevel($project))->toBe('low');
});

test('getRiskLevel returns high when more than half of tasks are overdue', function () {
    $service = new ProjectService();

    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id, 'deadline' => now()->subDay()]);
    $project->members()->attach($owner->id, ['role' => 'leader']);

    // 4 overdue tasks out of 5
    for ($i = 0; $i < 4; $i++) {
        $project->tasks()->create([
            'title'      => "Overdue $i",
            'status'     => 'pending',
            'due_date'   => now()->subDays(2),
            'created_by' => $owner->id,
            'priority'   => 'medium',
        ]);
    }
    $project->tasks()->create([
        'title'      => 'On Track',
        'status'     => 'done',
        'created_by' => $owner->id,
        'priority'   => 'medium',
    ]);

    expect($service->getRiskLevel($project))->toBe('high');
});

test('getMemberStats returns correct fields for each member', function () {
    $service = new ProjectService();

    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'leader']);
    $project->load('members');

    $stats = $service->getMemberStats($project);

    expect($stats)->toHaveCount(1);
    $member = $stats->first();
    expect($member)->toHaveKeys(['user', 'total_tasks', 'completed_tasks', 'overdue_tasks', 'participation', 'score', 'grade']);
});

test('logActivity creates an activity log entry', function () {
    $service = new ProjectService();

    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);

    $service->logActivity($project, $owner, 'test_action', 'Project', $project->id);

    $log = ActivityLog::where('project_id', $project->id)
        ->where('action', 'test_action')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($owner->id);
});
