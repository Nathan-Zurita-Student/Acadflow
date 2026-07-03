<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Exclusão DEFINITIVA de uma conta previamente marcada com soft delete.
 * O hard delete dispara os cascades de FK do banco (projetos, tarefas etc.).
 */
class PurgeDeletedUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $userId) {}

    public function handle(): void
    {
        $user = User::withTrashed()->find($this->userId);

        if (! $user || ! $user->trashed()) {
            return;
        }

        $user->forceDelete();
    }
}
