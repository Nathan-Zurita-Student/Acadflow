<?php

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;

test('sending invitation does not add member immediately', function () {
    $leader = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);
    $invitee = User::factory()->create();

    $this->actingAs($leader, 'sanctum')
        ->postJson("/api/projects/{$project->id}/invitations", [
            'user_id' => $invitee->id,
            'role'    => 'member',
        ])
        ->assertStatus(201)
        ->assertJson(['message' => 'Convite enviado com sucesso.']);

    // Invitation exists
    expect(ProjectInvitation::where('invited_user_id', $invitee->id)->exists())->toBeTrue();

    // Invitee is NOT a member yet
    expect($project->members()->where('user_id', $invitee->id)->exists())->toBeFalse();
});

test('accepting invitation adds member', function () {
    $leader  = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);

    $invitation = ProjectInvitation::create([
        'project_id'         => $project->id,
        'invited_user_id'    => $invitee->id,
        'invited_by_user_id' => $leader->id,
        'role'               => 'member',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->id}/respond", ['action' => 'accept'])
        ->assertStatus(200);

    expect($project->members()->where('user_id', $invitee->id)->exists())->toBeTrue();
    expect(ProjectInvitation::find($invitation->id)->status)->toBe('accepted');
});

test('declining invitation does not add member', function () {
    $leader  = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $leader->id]);
    $project->members()->attach($leader->id, ['role' => 'leader']);

    $invitation = ProjectInvitation::create([
        'project_id'         => $project->id,
        'invited_user_id'    => $invitee->id,
        'invited_by_user_id' => $leader->id,
        'role'               => 'member',
        'status'             => 'pending',
        'expires_at'         => now()->addDays(7),
    ]);

    $this->actingAs($invitee, 'sanctum')
        ->postJson("/api/invitations/{$invitation->id}/respond", ['action' => 'decline'])
        ->assertStatus(200);

    expect($project->members()->where('user_id', $invitee->id)->exists())->toBeFalse();
    expect(ProjectInvitation::find($invitation->id)->status)->toBe('declined');
});

test('non-leader cannot send invitation', function () {
    $owner   = User::factory()->create();
    $regular = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($regular->id, ['role' => 'member']);

    $this->actingAs($regular, 'sanctum')
        ->postJson("/api/projects/{$project->id}/invitations", [
            'user_id' => $invitee->id,
        ])
        ->assertStatus(403);

    expect($project->members()->where('user_id', $invitee->id)->exists())->toBeFalse();
});
