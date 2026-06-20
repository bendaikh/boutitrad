<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOrdersCommand extends Command
{
    protected $signature = 'data:clear-orders {--force : Exécuter sans confirmation}';

    protected $description = 'Vide toutes les commandes (conserve produits, clients, commerciaux)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Supprimer toutes les commandes ?',
            false,
        )) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $counts = DB::transaction(function () {
            $counts = [
                'Paiements commandes' => OrderPayment::query()->count(),
                'Commissions' => Commission::query()->count(),
                'Historiques commandes' => OrderStatusHistory::query()->count(),
                'Lignes commandes' => OrderItem::query()->count(),
                'Commandes' => Order::query()->count(),
            ];

            OrderPayment::query()->delete();
            Commission::query()->delete();
            OrderStatusHistory::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();

            return $counts;
        });

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-24s %d supprimé(s)', $label, $count));
        }

        $this->info('Commandes vidées.');

        return self::SUCCESS;
    }
}
