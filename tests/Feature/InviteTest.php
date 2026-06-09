<?php

use App\Models\Project;
use App\Models\ProjectInviteToken;
use App\Models\User;
test('leader can generate an invite link', function () {
    $leader = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);

    $this->actingAs($leader)->postJson("/api/projects/{$project->id}/invite", [
        'role' => 'member',
    ])->assertStatus(201)
      ->assertJsonStructure(['token', 'role', 'expires_at']);
});

test('member cannot generate any invite link', function () {
    $leader = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);
    $project->members()->attach($member->id, ['role' => 'member']);

    $this->actingAs($member)->postJson("/api/projects/{$project->id}/invite", [
        'role' => 'member',
    ])->assertStatus(403);
});

test('invite info endpoint is public', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $token = ProjectInviteToken::factory()->create([
        'project_id' => $project->id,
        'created_by' => $owner->id,
        'token'      => str_repeat('a', 64),
        'role'       => 'member',
        'expires_at' => now()->addDays(7),
    ]);

    $this->getJson("/api/invite/{$token->token}")
        ->assertStatus(200)
        ->assertJsonPath('project.id', $project->id);
});

test('user can accept an invite and join the project', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($owner->id, ['role' => 'leader']);

    $token = ProjectInviteToken::factory()->create([
        'project_id' => $project->id,
        'created_by' => $owner->id,
        'token'      => str_repeat('b', 64),
        'role'       => 'member',
        'expires_at' => now()->addDays(7),
    ]);

    $newUser = User::factory()->create();

    $this->actingAs($newUser)->postJson("/api/invite/{$token->token}/accept")
        ->assertStatus(200);

    expect($project->members()->where('user_id', $newUser->id)->exists())->toBeTrue();
});

test('expired invite cannot be accepted', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);

    $token = ProjectInviteToken::factory()->create([
        'project_id' => $project->id,
        'created_by' => $owner->id,
        'token'      => str_repeat('c', 64),
        'role'       => 'member',
        'expires_at' => now()->subDay(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson("/api/invite/{$token->token}/accept")
        ->assertStatus(404);
});
