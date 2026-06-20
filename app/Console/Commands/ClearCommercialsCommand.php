<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\CommercialObjective;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearCommercialsCommand extends Command
{
    protected $signature = 'data:clear-commercials {--force : Exécuter sans confirmation}';

    protected $description = 'Supprime tous les comptes commerciaux';

    public function handle(): int
    {
        $count = User::query()->where('role', UserRole::Commercial)->count();

        if ($count === 0) {
            $this->info('Aucun commercial à supprimer.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Supprimer {$count} commercial(aux) ? Les commandes et clients conservent leurs données (commercial dissocié).",
            false,
        )) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($count) {
            $commercialIds = User::query()
                ->where('role', UserRole::Commercial)
                ->pluck('id');

            $objectives = CommercialObjective::query()
                ->whereIn('user_id', $commercialIds)
                ->delete();

            $commissions = Commission::query()
                ->whereIn('user_id', $commercialIds)
                ->delete();

            $deletedUsers = User::query()
                ->where('role', UserRole::Commercial)
                ->delete();

            return [
                'commercials' => $deletedUsers,
                'objectives' => $objectives,
                'commissions' => $commissions,
            ];
        });

        $this->line(sprintf('Commerciaux supprimés     %d', $deleted['commercials']));
        $this->line(sprintf('Objectifs supprimés       %d', $deleted['objectives']));
        $this->line(sprintf('Commissions supprimées    %d', $deleted['commissions']));
        $this->info('Commerciaux supprimés. Commandes et clients conservés (lien commercial retiré).');

        return self::SUCCESS;
    }
}
