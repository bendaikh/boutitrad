<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CommercialObjective;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearCatalogDataCommand extends Command
{
    protected $signature = 'data:clear-catalog {--force : Exécuter sans confirmation}';

    protected $description = 'Vide les commandes, commerciaux, produits, catégories et marques';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Supprimer toutes les commandes, commerciaux, produits, catégories et marques ?',
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
                'Objectifs commerciaux' => CommercialObjective::query()->count(),
                'Commerciaux' => User::query()->where('role', UserRole::Commercial)->count(),
                'Mouvements stock' => StockMovement::query()->count(),
                'Variantes produits' => ProductVariant::query()->count(),
                'Produits' => Product::query()->count(),
                'Catégories' => Category::query()->count(),
                'Marques' => Brand::query()->count(),
            ];

            OrderPayment::query()->delete();
            Commission::query()->delete();
            OrderStatusHistory::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();

            $commercialIds = User::query()
                ->where('role', UserRole::Commercial)
                ->pluck('id');

            CommercialObjective::query()
                ->whereIn('user_id', $commercialIds)
                ->delete();

            User::query()
                ->where('role', UserRole::Commercial)
                ->delete();

            StockMovement::query()->delete();
            ProductVariant::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();

            return $counts;
        });

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-24s %d supprimé(s)', $label, $count));
        }

        $this->info('Données vidées : commandes, commerciaux, produits, catégories et marques.');
        $this->line('Conservés : clients, admin, livreurs, finance, villes, partenaires livraison.');

        return self::SUCCESS;
    }
}
