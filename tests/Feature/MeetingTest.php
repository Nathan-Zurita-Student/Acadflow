<?php

use App\Models\Project;
use App\Models\User;

function meetingProject(): array
{
    $leader = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);
    return compact('leader', 'project');
}

test('project member can list meetings', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $project->meetings()->create([
        'title'        => 'Sprint Review',
        'scheduled_at' => now()->addDays(3),
        'created_by'   => $leader->id,
    ]);

    $this->actingAs($leader)
        ->getJson("/api/projects/{$project->id}/meetings")
        ->assertStatus(200)
        ->assertJsonStructure([['id', 'title', 'scheduled_at']]);
});

test('project member can create a meeting', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $this->actingAs($leader)
        ->postJson("/api/projects/{$project->id}/meetings", [
            'title'        => 'Kickoff',
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
        ])
        ->assertStatus(201)
        ->assertJsonPath('title', 'Kickoff');
});

test('creating a meeting without title returns 422', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $this->actingAs($leader)
        ->postJson("/api/projects/{$project->id}/meetings", [
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('creating a meeting without scheduled_at returns 422', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $this->actingAs($leader)
        ->postJson("/api/projects/{$project->id}/meetings", ['title' => 'Missing Date'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['scheduled_at']);
});

test('project member can update a meeting', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $meeting = $project->meetings()->create([
        'title'        => 'Old Title',
        'scheduled_at' => now()->addDays(2),
        'created_by'   => $leader->id,
    ]);

    $this->actingAs($leader)
        ->putJson("/api/projects/{$project->id}/meetings/{$meeting->id}", ['title' => 'New Title'])
        ->assertStatus(200)
        ->assertJsonPath('title', 'New Title');
});

test('project member can delete a meeting they created', function () {
    ['leader' => $leader, 'project' => $project] = meetingProject();

    $meeting = $project->meetings()->create([
        'title'        => 'To Delete',
        'scheduled_at' => now()->addDays(1),
        'created_by'   => $leader->id,
    ]);

    $this->actingAs($leader)
        ->deleteJson("/api/projects/{$project->id}/meetings/{$meeting->id}")
        ->assertStatus(204);

    expect($project->meetings()->count())->toBe(0);
});

test('non-member cannot create a meeting', function () {
    ['project' => $project] = meetingProject();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->postJson("/api/projects/{$project->id}/meetings", [
            'title'        => 'Unauthorized',
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
        ])
        ->assertStatus(403);
});
