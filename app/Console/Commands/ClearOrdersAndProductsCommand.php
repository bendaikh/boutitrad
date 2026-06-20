<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearOrdersAndProductsCommand extends Command
{
    protected $signature = 'data:clear-orders-products {--force : Exécuter sans confirmation}';

    protected $description = 'Vide les commandes et les produits (conserve clients, catégories, marques)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Supprimer toutes les commandes et tous les produits ?',
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
                'Mouvements stock' => StockMovement::query()->count(),
                'Variantes produits' => ProductVariant::query()->count(),
                'Produits' => Product::query()->count(),
            ];

            OrderPayment::query()->delete();
            Commission::query()->delete();
            OrderStatusHistory::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();
            StockMovement::query()->delete();
            ProductVariant::query()->delete();
            Product::query()->delete();

            return $counts;
        });

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-24s %d supprimé(s)', $label, $count));
        }

        $this->info('Commandes et produits vidés.');
        $this->line('Conservés : clients, catégories, marques, commerciaux, admin.');

        return self::SUCCESS;
    }
}
