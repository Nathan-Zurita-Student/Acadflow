<?php

namespace App\Listeners\Auth;

use App\Events\Auth\AccountDeleted;
use App\Events\Auth\EmailChanged;
use App\Events\Auth\PasswordChanged;
use App\Events\Auth\SubscriptionCanceled;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

/**
 * Assina os eventos de autenticação (nativos do Laravel + próprios) e grava a
 * trilha de auditoria em auth_logs. Nunca registra dados sensíveis (ex.: senha).
 */
class AuthEventSubscriber
{
    public function __construct(private readonly AuthAuditLogger $logger) {}

    public function handleLogin(Login $event): void
    {
        $this->logger->log('login', $event->user instanceof User ? $event->user : null);
    }

    public function handleLogout(Logout $event): void
    {
        $this->logger->log('logout', $event->user instanceof User ? $event->user : null);
    }

    public function handleFailed(Failed $event): void
    {
        // Apenas o e-mail é registrado — nunca a senha presente em credentials.
        $this->logger->log('login_failed', $event->user instanceof User ? $event->user : null, [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }

    public function handleVerified(Verified $event): void
    {
        $this->logger->log('email_verified', $event->user instanceof User ? $event->user : null);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->log('password_reset', $event->user instanceof User ? $event->user : null);
    }

    public function handleRegistered(UserRegistered $event): void
    {
        $this->logger->log('register', $event->user);
    }

    public function handlePasswordChanged(PasswordChanged $event): void
    {
        $this->logger->log('password_changed', $event->user, ['reason' => $event->reason]);
    }

    public function handleEmailChanged(EmailChanged $event): void
    {
        $this->logger->log('email_changed', $event->user, [
            'old' => $event->oldEmail,
            'new' => $event->newEmail,
        ]);
    }

    public function handleAccountDeleted(AccountDeleted $event): void
    {
        $this->logger->log('account_deleted', $event->user, [
            'subscription_canceled' => $event->subscriptionCanceled,
        ]);
    }

    public function handleSubscriptionCanceled(SubscriptionCanceled $event): void
    {
        $this->logger->log('subscription_canceled', $event->user, ['reason' => $event->reason]);
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class                => 'handleLogin',
            Logout::class               => 'handleLogout',
            Failed::class               => 'handleFailed',
            Verified::class             => 'handleVerified',
            PasswordReset::class        => 'handlePasswordReset',
            UserRegistered::class       => 'handleRegistered',
            PasswordChanged::class      => 'handlePasswordChanged',
            EmailChanged::class         => 'handleEmailChanged',
            AccountDeleted::class       => 'handleAccountDeleted',
            SubscriptionCanceled::class => 'handleSubscriptionCanceled',
        ];
    }
}
