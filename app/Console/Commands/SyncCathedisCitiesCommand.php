<?php

namespace App\Console\Commands;

use App\Services\CathedisApiService;
use Illuminate\Console\Command;

class SyncCathedisCitiesCommand extends Command
{
    protected $signature = 'cathedis:sync-cities';

    protected $description = 'Synchronise les villes Cathedis (API si configurée, sinon liste par défaut)';

    public function handle(CathedisApiService $api): int
    {
        $count = $api->syncCities();

        $this->info("Villes Cathedis disponibles : {$count}");

        return self::SUCCESS;
    }
}
