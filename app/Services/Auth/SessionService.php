<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

/**
 * Gerenciamento das sessões (driver `database`) de um usuário: listar com
 * navegador/SO/IP/datas e encerrar individualmente, as outras, ou todas.
 */
class SessionService
{
    private function table()
    {
        return DB::table(config('session.table', 'sessions'));
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forUser(User $user, ?string $currentId): Collection
    {
        return $this->table()
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentId): array {
                $agent = new Agent();
                $agent->setUserAgent((string) ($session->user_agent ?? ''));

                $browser  = $agent->browser();
                $platform = $agent->platform();
                $version  = $browser ? $agent->version($browser) : null;

                return [
                    'id'          => $session->id,
                    'ip_address'  => $session->ip_address,
                    'browser'     => $browser ? trim($browser.' '.($version ?: '')) : 'Desconhecido',
                    'platform'    => $platform ?: 'Desconhecido',
                    'is_current'  => $session->id === $currentId,
                    'last_active' => $session->last_activity
                        ? Carbon::createFromTimestamp($session->last_activity)
                        : null,
                    'created_at'  => $session->created_at ? Carbon::parse($session->created_at) : null,
                ];
            })
            ->values();
    }

    public function destroy(User $user, string $id): void
    {
        $this->table()->where('user_id', $user->id)->where('id', $id)->delete();
    }

    public function destroyOthers(User $user, ?string $currentId): void
    {
        $this->table()
            ->where('user_id', $user->id)
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
            ->delete();
    }

    public function destroyAll(User $user): void
    {
        $this->table()->where('user_id', $user->id)->delete();
    }
}
