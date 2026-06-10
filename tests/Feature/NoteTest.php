<?php

use App\Models\Project;
use App\Models\User;

function noteProject(): array
{
    $leader = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);
    return compact('leader', 'project');
}

test('project member can list notes', function () {
    ['leader' => $leader, 'project' => $project] = noteProject();

    $project->notes()->create([
        'title'   => 'My Note',
        'content' => 'Some content',
        'user_id' => $leader->id,
    ]);

    $this->actingAs($leader)
        ->getJson("/api/projects/{$project->id}/notes")
        ->assertStatus(200)
        ->assertJsonStructure([['id', 'title', 'content']]);
});

test('project member can create a note', function () {
    ['leader' => $leader, 'project' => $project] = noteProject();

    $this->actingAs($leader)
        ->postJson("/api/projects/{$project->id}/notes", [
            'title'   => 'New Note',
            'content' => 'Content here',
        ])
        ->assertStatus(201)
        ->assertJsonPath('title', 'New Note');
});

test('creating a note without title returns 422', function () {
    ['leader' => $leader, 'project' => $project] = noteProject();

    $this->actingAs($leader)
        ->postJson("/api/projects/{$project->id}/notes", ['content' => 'No title'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('project member can update a note', function () {
    ['leader' => $leader, 'project' => $project] = noteProject();

    $note = $project->notes()->create([
        'title'   => 'Old Title',
        'user_id' => $leader->id,
    ]);

    $this->actingAs($leader)
        ->putJson("/api/projects/{$project->id}/notes/{$note->id}", ['title' => 'Updated Title'])
        ->assertStatus(200)
        ->assertJsonPath('title', 'Updated Title');
});

test('project member can delete their own note', function () {
    ['leader' => $leader, 'project' => $project] = noteProject();

    $note = $project->notes()->create([
        'title'   => 'To Delete',
        'user_id' => $leader->id,
    ]);

    $this->actingAs($leader)
        ->deleteJson("/api/projects/{$project->id}/notes/{$note->id}")
        ->assertStatus(204);

    expect($project->notes()->count())->toBe(0);
});

test('non-member cannot create a note', function () {
    ['project' => $project] = noteProject();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->postJson("/api/projects/{$project->id}/notes", ['title' => 'Unauthorized'])
        ->assertStatus(403);
});
