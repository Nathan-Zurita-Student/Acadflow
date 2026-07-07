<?php

use App\Http\Controllers\Api\Auth\AccountController;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\DataExportController;
use App\Http\Controllers\Api\Auth\EmailChangeController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\SessionManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Autenticação por SESSÃO/cookie (Sanctum SPA)
|--------------------------------------------------------------------------
| Estas rotas vivem no grupo `web` (StartSession + CSRF sempre ativos),
| garantindo sessão disponível de forma robusta — sem depender da detecção
| stateful por Origin/Referer. Mantêm o prefixo /api/auth/* e retornam JSON
| (bootstrap/app.php força JSON para api/*). Precisam vir ANTES do catch-all.
*/
Route::prefix('api/auth')->name('auth.')->group(function () {
    // Convidado (com rate limiting).
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')->name('register');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')->name('login');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:codes')->name('password.forgot');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:login')->name('password.reset');

    // Login com Google (OAuth) — usa o `state` da sessão (sem stateless).
    Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

    // Autenticadas por sessão, SEM exigir e-mail verificado.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [ProfileController::class, 'me'])->name('me');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('email/verify', [EmailVerificationController::class, 'verify'])->name('email.verify');
        Route::post('email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:codes')->name('email.resend');

        // Exigem e-mail verificado.
        Route::middleware('verified')->group(function () {
            Route::post('profile', [ProfileController::class, 'update'])->name('profile');
            Route::put('password', [PasswordController::class, 'update'])->name('password.update');

            Route::post('email/change', [EmailChangeController::class, 'request'])
                ->middleware('throttle:codes')->name('email.change');
            Route::post('email/change/confirm', [EmailChangeController::class, 'confirm'])->name('email.change.confirm');

            Route::get('sessions', [SessionManagementController::class, 'index'])->name('sessions.index');
            Route::delete('sessions/others', [SessionManagementController::class, 'destroyOthers'])->name('sessions.destroy-others');
            Route::delete('sessions/{id}', [SessionManagementController::class, 'destroy'])->name('sessions.destroy');

            Route::get('account/export', DataExportController::class)->name('account.export');
            Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
        });
    });
});

// SPA (catch-all) — precisa ser a última rota.
Route::get('/{any}', function () {
    return Inertia::render('App');
})->where('any', '.*');
