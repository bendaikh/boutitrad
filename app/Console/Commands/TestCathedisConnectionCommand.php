<?php

namespace App\Console\Commands;

use App\Services\CathedisApiService;
use Illuminate\Console\Command;

class TestCathedisConnectionCommand extends Command
{
    protected $signature = 'cathedis:test';

    protected $description = 'Teste la connexion à l\'API Cathedis';

    public function handle(CathedisApiService $api): int
    {
        $result = $api->testConnection();

        $this->line('API activée : '.($result['enabled'] ? 'oui' : 'non'));
        $this->line('Auth configurée : '.($result['configured'] ? 'oui' : 'non'));
        $this->line('Mode auth : '.($result['auth_mode'] ?? '—'));
        $this->line('URL : '.($result['api_url'] ?? '—'));
        $this->line('Partenaire : '.($result['partner'] ?? '—'));
        $this->line('Villes en base : '.($result['cities_count'] ?? 0));
        $this->newLine();
        $this->info($result['message']);

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
