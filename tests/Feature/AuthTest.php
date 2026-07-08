<?php

use App\Models\User;

test('user can register', function () {
    $response = $this->postJson('/api/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'Str0ng#Pass1',
        'password_confirmation' => 'Str0ng#Pass1',
        'terms'                 => true,
    ]);

    // Contrato novo: sessão/cookie (sem token) e usuário ainda não verificado.
    $response->assertStatus(201)
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'email_verified']])
        ->assertJsonPath('user.email_verified', false);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('registered user has member role', function () {
    $this->postJson('/api/auth/register', [
        'name'                  => 'Test User',
        'email'                 => 'member@example.com',
        'password'              => 'Str0ng#Pass1',
        'password_confirmation' => 'Str0ng#Pass1',
        'terms'                 => true,
    ]);

    $user = User::where('email', 'member@example.com')->first();
    expect($user->role)->toBe('member');
});

test('user can login', function () {
    $user = User::factory()->create(['password' => bcrypt('Secret#123')]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'Secret#123',
    ]);

    $response->assertStatus(200)->assertJsonStructure(['user' => ['id', 'email']]);
});

test('login fails with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct#123')]);

    $this->postJson('/api/auth/login', [
        'email'    => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422);
});

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('user.id', $user->id);
});

test('unauthenticated request is rejected', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});
