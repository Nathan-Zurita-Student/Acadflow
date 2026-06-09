<?php

use App\Models\User;
test('user can register', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('registered user has member role', function () {
    $this->postJson('/api/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'member@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'member@example.com')->first();
    expect($user->role)->toBe('member');
});

test('user can login', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'user']);
});

test('login fails with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);

    $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422);
});

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('id', $user->id);
});

test('unauthenticated request is rejected', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});
