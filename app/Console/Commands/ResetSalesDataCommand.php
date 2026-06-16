<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Brand;
use App\Models\CashTransaction;
use App\Models\Category;
use App\Models\Client;
use App\Models\Commission;
use App\Models\CommercialObjective;
use App\Models\Expense;
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

class ResetSalesDataCommand extends Command
{
    protected $signature = 'sales:reset {--force : Exécuter sans confirmation}';

    protected $description = 'Réinitialise ventes, finance et catalogue produits';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Supprimer commandes, clients, commerciaux, charges, trésorerie, produits, catégories et marques ?', false)) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            OrderPayment::query()->delete();
            Commission::query()->delete();
            OrderStatusHistory::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();
            Client::query()->delete();

            $commercialIds = User::query()
                ->where('role', UserRole::Commercial)
                ->pluck('id');

            CommercialObjective::query()
                ->whereIn('user_id', $commercialIds)
                ->delete();

            User::query()
                ->where('role', UserRole::Commercial)
                ->delete();

            Expense::query()->delete();
            CashTransaction::query()->delete();

            StockMovement::query()->delete();
            ProductVariant::query()->delete();
            Product::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();

            DB::table('notifications')->delete();
        });

        $this->info('Données réinitialisées : ventes, finance, produits, catégories et marques.');
        $this->line('Comptes conservés : superadmin, livreur, gestionnaire stock.');
        $this->line('Trésoreries (liste) et partenaires livraison conservés — vous pouvez tout resaisir.');

        return self::SUCCESS;
    }
}
