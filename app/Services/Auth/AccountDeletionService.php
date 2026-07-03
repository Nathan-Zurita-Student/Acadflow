<?php

namespace App\Services\Auth;

use App\Events\Auth\AccountDeleted;
use App\Models\EmailChangeCode;
use App\Models\EmailVerificationCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\Auth\AccountDeletedNotification;
use App\Services\AsaasService;
use App\Support\SafeNotify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Exclusão de conta profissional: cancela assinatura ativa, revoga tokens,
 * encerra sessões, remove códigos pendentes, libera o e-mail (anonimização) e
 * faz soft delete. A exclusão definitiva ocorre depois via Job de purge.
 */
class AccountDeletionService
{
    public function __construct(
        private readonly AsaasService $asaas,
        private readonly SessionService $sessions,
    ) {}

    public function delete(User $user): void
    {
        $originalEmail = $user->email;
        $subscriptionCanceled = false;

        DB::transaction(function () use ($user, &$subscriptionCanceled): void {
            // 1) Cancela assinatura ativa no ASAAS (evita cobranças futuras).
            if ($user->asaas_subscription_id) {
                try {
                    $subscriptionCanceled = $this->asaas->cancelSubscription($user->asaas_subscription_id);
                } catch (\Throwable $e) {
                    // Gateway indisponível não deve travar a exclusão; registramos
                    // e marcamos como cancelado localmente para reconciliação.
                    report($e);
                }

                $user->plan_status = 'canceled';
                $user->pending_plan = null;
            }

            // 2) Revoga tokens de API e encerra todas as sessões.
            $user->tokens()->delete();
            $this->sessions->destroyAll($user);

            // 3) Remove códigos OTP pendentes.
            EmailVerificationCode::where('user_id', $user->id)->delete();
            PasswordResetCode::where('user_id', $user->id)->delete();
            EmailChangeCode::where('user_id', $user->id)->delete();

            // 4) Libera o e-mail (permite novo cadastro) e faz soft delete.
            $user->forceFill([
                'email'     => 'deleted+'.$user->id.'@acadflow.local',
                'google_id' => null,
            ])->save();

            $user->delete();
        });

        // Fora da transação: avisos e auditoria. O e-mail é best-effort — uma
        // falha de envio NÃO reverte nem quebra a exclusão já concluída.
        SafeNotify::attempt(
            fn () => Notification::route('mail', $originalEmail)->notify(new AccountDeletedNotification()),
            'account_deleted',
        );

        event(new AccountDeleted($user, $subscriptionCanceled));
    }
}
