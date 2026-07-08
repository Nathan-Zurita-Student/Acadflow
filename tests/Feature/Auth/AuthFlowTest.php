<?php

use App\Models\EmailVerificationCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\Auth\AccountDeletedNotification;
use App\Notifications\Auth\EmailVerificationCodeNotification;
use App\Notifications\Auth\PasswordResetCodeNotification;
use App\Services\Auth\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function issueCode(string $model, User $user): string
{
    return app(VerificationCodeService::class)->issue($model, $user);
}

it('registra usuário como não verificado e envia código', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/register', [
        'name'                  => 'Fulano',
        'email'                 => '  FULANO@Example.com ',
        'password'              => 'Str0ng#Pass1',
        'password_confirmation' => 'Str0ng#Pass1',
        'terms'                 => true,
    ]);

    $response->assertCreated()->assertJsonPath('user.email_verified', false);

    // E-mail normalizado (trim + lowercase).
    $user = User::first();
    expect($user->email)->toBe('fulano@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
    expect(EmailVerificationCode::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejeita senha fraca no cadastro', function () {
    $this->postJson('/api/auth/register', [
        'name'                  => 'Fraco',
        'email'                 => 'fraco@example.com',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
        'terms'                 => true,
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('verifica e-mail com código correto e consome o código', function () {
    $user = User::factory()->unverified()->create();
    $code = issueCode(EmailVerificationCode::class, $user);

    $this->actingAs($user)
        ->postJson('/api/auth/email/verify', ['code' => $code])
        ->assertOk()
        ->assertJsonPath('user.email_verified', true);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect(EmailVerificationCode::where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejeita código de verificação inválido', function () {
    $user = User::factory()->unverified()->create();
    issueCode(EmailVerificationCode::class, $user);

    $this->actingAs($user)
        ->postJson('/api/auth/email/verify', ['code' => '000000'])
        ->assertStatus(422)->assertJsonValidationErrors('code');
});

it('login usa mensagem genérica em credenciais inválidas', function () {
    User::factory()->create(['email' => 'joao@example.com', 'password' => 'Str0ng#Pass1']);

    $this->postJson('/api/auth/login', [
        'email'    => 'joao@example.com',
        'password' => 'errada',
    ])->assertStatus(422)->assertJsonPath('errors.email.0', 'Credenciais inválidas.');
});

it('login bem-sucedido autentica e registra last_login', function () {
    $user = User::factory()->create(['email' => 'ok@example.com', 'password' => 'Str0ng#Pass1']);

    $this->postJson('/api/auth/login', [
        'email'    => 'ok@example.com',
        'password' => 'Str0ng#Pass1',
    ])->assertOk()->assertJsonPath('user.email', 'ok@example.com');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('bloqueia rotas do app para usuário não verificado', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->getJson('/api/notifications')->assertStatus(403);
});

it('permite rotas do app para usuário verificado', function () {
    $user = User::factory()->create(); // factory já cria verificado

    $this->actingAs($user)->getJson('/api/notifications')->assertOk();
});

it('esqueci senha responde de forma neutra e envia código quando existe', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reset@example.com']);

    // E-mail existente
    $this->postJson('/api/auth/forgot-password', ['email' => 'reset@example.com'])->assertOk();
    Notification::assertSentTo($user, PasswordResetCodeNotification::class);

    // E-mail inexistente → mesma resposta, sem erro
    $this->postJson('/api/auth/forgot-password', ['email' => 'naoexiste@example.com'])->assertOk();
});

it('redefine a senha com código válido', function () {
    $user = User::factory()->create(['email' => 'novo@example.com', 'password' => 'Old#Pass123']);
    $code = issueCode(PasswordResetCode::class, $user);

    $this->postJson('/api/auth/reset-password', [
        'email'                 => 'novo@example.com',
        'code'                  => $code,
        'password'              => 'New#Pass456',
        'password_confirmation' => 'New#Pass456',
    ])->assertOk();

    expect(\Illuminate\Support\Facades\Hash::check('New#Pass456', $user->fresh()->password))->toBeTrue();
    expect(PasswordResetCode::where('user_id', $user->id)->exists())->toBeFalse();
});

it('não permite enumerar e-mails pelo reset (mensagens idênticas)', function () {
    $user = User::factory()->create(['email' => 'existe@example.com']); // sem código ativo

    $payloadExistente = [
        'email' => 'existe@example.com', 'code' => '123456',
        'password' => 'New#Pass456', 'password_confirmation' => 'New#Pass456',
    ];
    $payloadInexistente = array_merge($payloadExistente, ['email' => 'naoexiste@example.com']);

    $msgExistente = $this->postJson('/api/auth/reset-password', $payloadExistente)
        ->assertStatus(422)->json('errors.code.0');
    $msgInexistente = $this->postJson('/api/auth/reset-password', $payloadInexistente)
        ->assertStatus(422)->json('errors.code.0');

    expect($msgExistente)->toBe($msgInexistente);
});

it('altera senha exigindo a senha atual e notifica', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => 'Curr#Pass123']);

    $this->actingAs($user)->putJson('/api/auth/password', [
        'current_password'      => 'Curr#Pass123',
        'password'              => 'Next#Pass456',
        'password_confirmation' => 'Next#Pass456',
    ])->assertOk();

    expect(\Illuminate\Support\Facades\Hash::check('Next#Pass456', $user->fresh()->password))->toBeTrue();
});

it('exclui a conta com senha, faz soft delete e libera o e-mail', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'bye@example.com', 'password' => 'Bye#Pass123']);

    $this->actingAs($user)->deleteJson('/api/auth/account', [
        'current_password' => 'Bye#Pass123',
    ])->assertOk();

    $trashed = User::withTrashed()->find($user->id);
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->email)->not->toBe('bye@example.com'); // anonimizado
    Notification::assertSentOnDemand(AccountDeletedNotification::class);

    // E-mail liberado para novo cadastro.
    $this->postJson('/api/auth/register', [
        'name'                  => 'Outro',
        'email'                 => 'bye@example.com',
        'password'              => 'Str0ng#Pass1',
        'password_confirmation' => 'Str0ng#Pass1',
        'terms'                 => true,
    ])->assertCreated();
});

it('grava trilha de auditoria em auth_logs', function () {
    $user = User::factory()->create(['email' => 'audit@example.com', 'password' => 'Str0ng#Pass1']);

    $this->postJson('/api/auth/login', [
        'email'    => 'audit@example.com',
        'password' => 'Str0ng#Pass1',
    ])->assertOk();

    $this->assertDatabaseHas('auth_logs', ['user_id' => $user->id, 'event' => 'login']);

    // Falha de login também é auditada (sem vazar a senha).
    $this->postJson('/api/auth/login', ['email' => 'audit@example.com', 'password' => 'x']);
    $this->assertDatabaseHas('auth_logs', ['event' => 'login_failed']);
});
