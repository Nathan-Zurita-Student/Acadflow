<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Envio de e-mail "best-effort": uma falha do provedor (indisponibilidade,
 * destinatário recusado em sandbox etc.) é registrada, mas NUNCA quebra o
 * fluxo principal (cadastro, exclusão de conta, troca de senha/e-mail).
 */
final class SafeNotify
{
    public static function attempt(Closure $send, string $context): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning("Falha ao enviar e-mail [{$context}]: ".$e->getMessage());
        }
    }
}
