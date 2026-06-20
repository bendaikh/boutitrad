<?php

namespace App\Console\Commands;

use App\Services\CathedisStatusSyncService;
use Illuminate\Console\Command;

class SyncCathedisOrderStatusesCommand extends Command
{
    protected $signature = 'cathedis:sync-orders {--limit=100 : Nombre maximum de commandes à vérifier}';

    protected $description = 'Synchronise les statuts des commandes depuis Cathedis';

    public function handle(CathedisStatusSyncService $sync): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $summary = $sync->syncPendingOrders($limit);

        $this->info(sprintf(
            'Cathedis : %d vérifiée(s), %d mise(s) à jour, %d ignorée(s), %d erreur(s).',
            $summary['checked'],
            $summary['updated'],
            $summary['skipped'],
            $summary['errors'],
        ));

        return self::SUCCESS;
    }
}
