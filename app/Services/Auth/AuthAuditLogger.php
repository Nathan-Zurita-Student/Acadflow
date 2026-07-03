<?php

namespace App\Services\Auth;

use App\Models\AuthLog;
use App\Models\User;

/** Centraliza a escrita da trilha de auditoria de autenticação (auth_logs). */
class AuthAuditLogger
{
    public function log(string $event, ?User $user, array $properties = []): void
    {
        $request = request();

        AuthLog::create([
            'user_id'    => $user?->id,
            'event'      => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties ?: null,
        ]);
    }
}
