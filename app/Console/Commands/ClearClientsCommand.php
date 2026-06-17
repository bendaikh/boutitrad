<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearClientsCommand extends Command
{
    protected $signature = 'data:clear-clients {--force : Exécuter sans confirmation}';

    protected $description = 'Vide tous les clients';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Supprimer tous les clients ?', false)) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $count = DB::transaction(function () {
            $clients = Client::query()->get();
            $total = $clients->count();

            foreach ($clients as $client) {
                if ($client->photo) {
                    Storage::disk('public')->delete($client->photo);
                }
            }

            Client::query()->delete();

            return $total;
        });

        $this->line(sprintf('Clients                 %d supprimé(s)', $count));
        $this->info('Fiche clients vidée.');

        return self::SUCCESS;
    }
}
