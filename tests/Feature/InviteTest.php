<?php

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectInviteToken;
use App\Models\User;

function pendingInvitation(Project $project, User $invitee, User $inviter): ProjectInvitation
{
    return ProjectInvitation::create([
        'project_id'         => $project->id,
        'invited_user_id'    => $invitee->id,
        'invited_by_user_id' => $inviter->id,
        'role'               => 'member',
        'status'             => ProjectInvitation::STATUS_PENDING,
        'expires_at'         => now()->addDays(7),
    ]);
}
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

test('emailed invitation opens a public preview with project and inviter', function () {
    $inviter = User::factory()->create(['name' => 'Ana Souza']);
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $inviter->id, 'name' => 'TCC Redes']);

    $invitation = pendingInvitation($project, $invitee, $inviter);

    expect($invitation->token)->not->toBeEmpty();

    $this->getJson("/api/invite/{$invitation->token}")
        ->assertStatus(200)
        ->assertJsonPath('project.name', 'TCC Redes')
        ->assertJsonPath('invited_by.name', 'Ana Souza');
});

test('invited user accepts the emailed invitation and joins', function () {
    $inviter = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $inviter->id]);
    $project->members()->attach($inviter->id, ['role' => 'leader']);

    $invitation = pendingInvitation($project, $invitee, $inviter);

    $this->actingAs($invitee)->postJson("/api/invite/{$invitation->token}/accept")
        ->assertStatus(200)
        ->assertJsonPath('project_id', $project->id);

    expect($project->members()->where('user_id', $invitee->id)->exists())->toBeTrue();
    expect($invitation->fresh()->status)->toBe(ProjectInvitation::STATUS_ACCEPTED);
});

test('emailed invitation cannot be accepted by another account', function () {
    $inviter   = User::factory()->create();
    $invitee   = User::factory()->create();
    $outsider  = User::factory()->create();
    $project   = Project::factory()->create(['owner_id' => $inviter->id]);

    $invitation = pendingInvitation($project, $invitee, $inviter);

    $this->actingAs($outsider)->postJson("/api/invite/{$invitation->token}/accept")
        ->assertStatus(403)
        ->assertJsonPath('wrong_account', true);

    expect($project->members()->where('user_id', $outsider->id)->exists())->toBeFalse();
    expect($invitation->fresh()->status)->toBe(ProjectInvitation::STATUS_PENDING);
});

test('already accepted invitation is no longer a valid link', function () {
    $inviter = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $inviter->id]);

    $invitation = pendingInvitation($project, $invitee, $inviter);
    $invitation->update(['status' => ProjectInvitation::STATUS_ACCEPTED]);

    $this->getJson("/api/invite/{$invitation->token}")->assertStatus(404);
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
