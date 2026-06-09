<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
test('leader can create a task', function () {
    [$leader, $project] = createLeaderAndProject();

    $this->actingAs($leader)->postJson("/api/projects/{$project->id}/tasks", [
        'title'    => 'New Task',
        'status'   => 'backlog',
        'priority' => 'medium',
    ])->assertStatus(201)
      ->assertJsonPath('title', 'New Task');
});

test('member can create a task', function () {
    [$leader, $project] = createLeaderAndProject();
    $member = User::factory()->create();
    $project->members()->attach($member->id, ['role' => 'member']);

    $this->actingAs($member)->postJson("/api/projects/{$project->id}/tasks", [
        'title'  => 'Member Task',
        'status' => 'backlog',
    ])->assertStatus(201);
});

test('task can be updated', function () {
    [$leader, $project] = createLeaderAndProject();
    $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $leader->id]);

    $this->actingAs($leader)->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
        'title'  => 'Updated Task',
        'status' => 'in_progress',
    ])->assertStatus(200)
      ->assertJsonPath('title', 'Updated Task');
});

test('member can submit task for approval', function () {
    [$leader, $project] = createLeaderAndProject();
    $member = User::factory()->create();
    $project->members()->attach($member->id, ['role' => 'member']);
    $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $member->id]);

    $this->actingAs($member)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/submit-approval")
        ->assertStatus(200)
        ->assertJsonPath('approval_status', 'pending');
});

test('leader can approve a task', function () {
    [$leader, $project] = createLeaderAndProject();
    $task = Task::factory()->create([
        'project_id'      => $project->id,
        'created_by'      => $leader->id,
        'approval_status' => 'pending',
    ]);

    $this->actingAs($leader)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/approve")
        ->assertStatus(200)
        ->assertJsonPath('approval_status', 'approved');
});

test('leader can reject a task with a note', function () {
    [$leader, $project] = createLeaderAndProject();
    $task = Task::factory()->create([
        'project_id'      => $project->id,
        'created_by'      => $leader->id,
        'approval_status' => 'pending',
    ]);

    $this->actingAs($leader)->postJson("/api/projects/{$project->id}/tasks/{$task->id}/reject", [
        'note' => 'Needs more detail',
    ])->assertStatus(200)
      ->assertJsonPath('approval_status', 'rejected')
      ->assertJsonPath('rejection_note', 'Needs more detail');
});

// ── helpers ──────────────────────────────────────────────────────────────────

function createLeaderAndProject(): array
{
    $leader = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);
    return [$leader, $project];
}
