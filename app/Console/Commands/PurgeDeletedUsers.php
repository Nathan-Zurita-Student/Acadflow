<?php

namespace App\Console\Commands;

use App\Jobs\PurgeDeletedUser;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Varre contas com soft delete além do período de carência e agenda a
 * exclusão definitiva de cada uma (via Job).
 */
class PurgeDeletedUsers extends Command
{
    protected $signature = 'auth:purge-deleted {--days=30 : Dias de carência antes da exclusão definitiva}';

    protected $description = 'Exclui definitivamente contas com soft delete além do período de carência.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $ids = User::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->pluck('id');

        $ids->each(fn (int $id) => PurgeDeletedUser::dispatch($id));

        $this->info("Agendada a exclusão definitiva de {$ids->count()} conta(s).");

        return self::SUCCESS;
    }
}
