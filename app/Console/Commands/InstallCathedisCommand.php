<?php

namespace App\Console\Commands;

use App\Models\DeliveryPartner;
use App\Services\CathedisApiService;
use App\Support\CathedisConfig;
use Illuminate\Console\Command;

class InstallCathedisCommand extends Command
{
    protected $signature = 'cathedis:install';

    protected $description = 'Synchronise la config Cathedis (.env → base) et vérifie la connexion';

    public function handle(CathedisApiService $api): int
    {
        $this->call('config:clear');

        CathedisConfig::syncFromEnv();

        DeliveryPartner::firstOrCreate(['code' => 'cathedis'], [
            'name' => 'Cathedis',
            'contact_email' => 'contact@cathedis.ma',
            'contact_phone' => '+212 520 255 255',
            'api_url' => config('cathedis.api_url'),
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->info('Configuration Cathedis enregistrée en base.');

        return $this->call('cathedis:test');
    }
}
