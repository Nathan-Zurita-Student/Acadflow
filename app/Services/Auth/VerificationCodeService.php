<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Núcleo reutilizável dos códigos OTP (verificação de e-mail, recuperação de
 * senha e troca de e-mail). Responsabilidades:
 *  - gerar código numérico de 6 dígitos;
 *  - invalidar códigos anteriores do usuário;
 *  - persistir apenas o HASH (nunca o texto puro);
 *  - validar respeitando expiração (10 min) e limite de tentativas (anti brute-force).
 */
class VerificationCodeService
{
    public const EXPIRY_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;

    /**
     * Emite um novo código, invalidando os anteriores do usuário.
     * Retorna o código em texto puro para envio (nunca é persistido em claro).
     *
     * @param  class-string<VerificationCode>  $model
     */
    public function issue(string $model, User $user, array $extra = []): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $model::where('user_id', $user->id)->delete();

        $model::create(array_merge([
            'user_id'    => $user->id,
            'code_hash'  => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ], $extra));

        return $code;
    }

    /**
     * Valida o código informado. Em sucesso devolve o registro (ainda NÃO
     * consumido — o chamador decide quando apagar via consume()). Em falha,
     * lança ValidationException no campo `code`.
     *
     * @param  class-string<VerificationCode>  $model
     */
    public function validateCode(string $model, User $user, string $code): VerificationCode
    {
        /** @var VerificationCode|null $record */
        $record = $model::where('user_id', $user->id)->latest('id')->first();

        if (! $record || $record->isExpired()) {
            $record?->delete();
            $this->fail('Código expirado ou inexistente. Solicite um novo.');
        }

        if ($record->hasTooManyAttempts(self::MAX_ATTEMPTS)) {
            $record->delete();
            $this->fail('Número máximo de tentativas atingido. Solicite um novo código.');
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');
            $this->fail('Código inválido.');
        }

        return $record;
    }

    public function consume(VerificationCode $record): void
    {
        $record->delete();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['code' => [$message]]);
    }
}
