<?php

use App\Models\Project;
use App\Models\User;
test('user can create a project and becomes its leader', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/projects', [
        'name'        => 'My Project',
        'description' => 'Test project',
        'status'      => 'planning',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('name', 'My Project');

    $project = Project::where('name', 'My Project')->first();
    expect($project)->not->toBeNull();

    $member = $project->members()->where('user_id', $user->id)->first();
    expect($member->pivot->role)->toBe('leader');
});

test('user can list their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $project->members()->attach($user->id, ['role' => 'leader']);

    $this->actingAs($user)->getJson('/api/projects')
        ->assertStatus(200)
        ->assertJsonStructure([['id', 'name']]);
});

test('project leader can update the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $user->id]);
    $project->members()->attach($user->id, ['role' => 'leader']);

    $this->actingAs($user)->putJson("/api/projects/{$project->id}", [
        'name'   => 'Updated Name',
        'status' => 'active',
    ])->assertStatus(200)
      ->assertJsonPath('name', 'Updated Name');
});

test('non-member cannot access project dashboard', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'leader']);

    $this->actingAs($other)->getJson("/api/projects/{$project->id}/dashboard")
        ->assertStatus(403);
});

test('project member can view dashboard', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'leader']);
    $project->members()->attach($member->id, ['role' => 'member']);

    $this->actingAs($member)->getJson("/api/projects/{$project->id}/dashboard")
        ->assertStatus(200);
});
