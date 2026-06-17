<?php

namespace App\Console\Commands;

use App\Models\DeliveryPartner;
use App\Services\CathedisApiService;
use Illuminate\Console\Command;

class InstallCathedisCommand extends Command
{
    protected $signature = 'cathedis:install';

    protected $description = 'Crée le partenaire Cathedis et vérifie la connexion (config manuelle requise dans l\'admin)';

    public function handle(CathedisApiService $api): int
    {
        $this->call('config:clear');

        DeliveryPartner::firstOrCreate(['code' => 'cathedis'], [
            'name' => 'Cathedis',
            'contact_email' => 'contact@cathedis.ma',
            'contact_phone' => '+212 520 255 255',
            'api_url' => config('cathedis.api_url'),
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->info('Partenaire Cathedis prêt.');
        $this->line('Configurez les identifiants et paramètres dans Livraison > Partenaires.');

        return $this->call('cathedis:test');
    }
}
