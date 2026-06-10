<?php

use App\Models\Notification;
use App\Models\User;

test('user can list their notifications', function () {
    $user = User::factory()->create();

    Notification::create([
        'user_id' => $user->id,
        'type'    => 'task_assigned',
        'title'   => 'Tarefa atribuída',
        'message' => 'Você foi alocado em uma tarefa.',
    ]);

    $this->actingAs($user)
        ->getJson('/api/notifications')
        ->assertStatus(200)
        ->assertJsonStructure(['items' => [['id', 'title', 'message', 'read_at']], 'unread']);
});

test('user can mark a notification as read', function () {
    $user = User::factory()->create();

    $notification = Notification::create([
        'user_id' => $user->id,
        'type'    => 'task_assigned',
        'title'   => 'Tarefa atribuída',
        'message' => 'Você foi alocado.',
    ]);

    $this->actingAs($user)
        ->postJson("/api/notifications/{$notification->id}/read")
        ->assertStatus(200);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();

    Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'A', 'message' => 'msg1']);
    Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'B', 'message' => 'msg2']);

    $this->actingAs($user)
        ->postJson('/api/notifications/read-all')
        ->assertStatus(200);

    $unread = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    expect($unread)->toBe(0);
});

test('user cannot mark another user notification as read', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $notification = Notification::create([
        'user_id' => $owner->id,
        'type'    => 'test',
        'title'   => 'Private',
        'message' => 'msg',
    ]);

    $this->actingAs($other)
        ->postJson("/api/notifications/{$notification->id}/read")
        ->assertStatus(403);
});
