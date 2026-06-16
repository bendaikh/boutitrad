<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\CashTransaction;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function stats(?User $user = null): array
    {
        $ordersQuery = Order::query();
        $this->scopeOrdersForUser($ordersQuery, $user);

        $revenue = (clone $ordersQuery)
            ->where('status', OrderStatus::Livree)
            ->sum('total');

        $totalOrders = (clone $ordersQuery)->count();

        $expenses = Expense::query()->sum('amount');
        $netProfit = $revenue - $expenses;

        $cashIn = CashTransaction::where('type', 'in')->sum('amount');
        $cashOut = CashTransaction::where('type', 'out')->sum('amount');
        $treasury = $cashIn - $cashOut;

        return [
            'revenue' => $revenue,
            'total_orders' => $totalOrders,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'treasury' => $treasury,
            'clients_count' => Client::where('is_active', true)->count(),
            'products_count' => Product::where('is_active', true)->count(),
            'low_stock_count' => Product::whereColumn('quantity', '<=', 'min_quantity')->count(),
        ];
    }

    public function orderDistributionChart(?User $user = null): array
    {
        $pendingStatuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::activeStatuses());
        $year = now()->year;

        $query = Order::query()
            ->whereYear('created_at', $year)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Livree->value."' THEN 1 ELSE 0 END) as validated"),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", $pendingStatuses)."') THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Annulee->value."' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Retournee->value."' THEN 1 ELSE 0 END) as returns"),
            );

        $this->scopeOrdersForUser($query, $user);

        $rows = $query->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $labels = [];
        $validated = [];
        $pending = [];
        $cancelled = [];
        $returns = [];

        foreach ($this->yearMonths($year) as $monthKey => $monthLabel) {
            $row = $rows->get($monthKey);

            $labels[] = $monthLabel;
            $validated[] = $row ? (int) $row->validated : 0;
            $pending[] = $row ? (int) $row->pending : 0;
            $cancelled[] = $row ? (int) $row->cancelled : 0;
            $returns[] = $row ? (int) $row->returns : 0;
        }

        return compact('labels', 'validated', 'pending', 'cancelled', 'returns');
    }

    public function commercialPerformance(?User $user = null): array
    {
        if ($user?->isCommercial()) {
            return Order::where('commercial_id', $user->id)
                ->where('status', OrderStatus::Livree)
                ->select('commercial_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('commercial_id')
                ->with('commercial:id,name')
                ->get()
                ->map(fn ($row) => [
                    'name' => $user->name,
                    'total' => $row->total,
                    'count' => $row->count,
                ])
                ->toArray();
        }

        return Order::where('status', OrderStatus::Livree)
            ->whereNotNull('commercial_id')
            ->select('commercial_id', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('commercial_id')
            ->with('commercial:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->commercial?->name ?? 'N/A',
                'total' => $row->total,
                'count' => $row->count,
            ])
            ->toArray();
    }

    public function commercialSalesByMonth(?User $user, int $year, int $month): array
    {
        $query = Order::query()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('commercial_id')
            ->select(
                'commercial_id',
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Livree->value."' THEN 1 ELSE 0 END) as ventes_confi"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Annulee->value."' THEN 1 ELSE 0 END) as ventes_annu"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Retournee->value."' THEN 1 ELSE 0 END) as ventes_retour"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Livree->value."' THEN total ELSE 0 END) as chiffre_realise"),
            )
            ->groupBy('commercial_id')
            ->with('commercial:id,name,role');

        $this->scopeOrdersForUser($query, $user);

        return $query->get()
            ->map(fn ($row) => [
                'id' => $row->commercial?->formattedCommercialId() ?? '—',
                'name' => $row->commercial?->name ?? 'N/A',
                'ventes_confi' => (int) $row->ventes_confi,
                'ventes_annu' => (int) $row->ventes_annu,
                'ventes_retour' => (int) $row->ventes_retour,
                'chiffre_realise' => (float) $row->chiffre_realise,
            ])
            ->sortByDesc('chiffre_realise')
            ->values()
            ->toArray();
    }

    public function topProductsByMonth(?User $user, int $year, int $month, int $limit = 15): array
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $month)
            ->where('orders.status', OrderStatus::Livree)
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.total) as amount'),
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity_sold')
            ->limit($limit);

        $this->scopeOrdersForUser($query, $user, 'orders');

        return $query->get()
            ->map(fn ($row, $index) => [
                'rank' => $index + 1,
                'product_name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'amount' => (float) $row->amount,
            ])
            ->toArray();
    }

    public function activeCitiesByMonth(?User $user, int $year, int $month): array
    {
        $query = Order::query()
            ->join('clients', 'clients.id', '=', 'orders.client_id')
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $month)
            ->where('orders.status', OrderStatus::Livree)
            ->whereNotNull('clients.city')
            ->where('clients.city', '!=', '')
            ->select(
                'clients.city',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.total) as amount'),
            )
            ->groupBy('clients.city')
            ->orderByDesc('orders_count');

        $this->scopeOrdersForUser($query, $user, 'orders');

        return $query->get()
            ->map(fn ($row) => [
                'city' => $row->city,
                'orders_count' => (int) $row->orders_count,
                'amount' => (float) $row->amount,
            ])
            ->toArray();
    }

    public function monthLabel(int $year, int $month): string
    {
        $labels = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($labels[$month] ?? '').' '.$year;
    }

    public function alerts(?User $user = null): array
    {
        $alerts = [];

        if (! $user || $user->isSuperAdmin() || $user->isGestionnaireStock()) {
            $lowStock = Product::whereColumn('quantity', '<=', 'min_quantity')->limit(3)->get();
            foreach ($lowStock as $product) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Rupture imminente : {$product->name} ({$product->quantity} restants)",
                ];
            }
        }

        if (! $user || $user->isSuperAdmin()) {
            $pending = Order::whereIn('status', [OrderStatus::Nouvelle, OrderStatus::EnCours])->count();
            if ($pending > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "{$pending} commande(s) en attente de validation",
                ];
            }
        }

        return $alerts;
    }

    private function yearMonths(int $year): array
    {
        $monthLabels = [
            '01' => 'JAN', '02' => 'FÉV', '03' => 'MAR', '04' => 'AVR',
            '05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AOÛ',
            '09' => 'SEP', '10' => 'OCT', '11' => 'NOV', '12' => 'DÉC',
        ];

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%04d-%02d', $year, $month);
            $months[$monthKey] = $monthLabels[sprintf('%02d', $month)];
        }

        return $months;
    }

    private function scopeOrdersForUser($query, ?User $user, ?string $table = null): void
    {
        if (! $user) {
            return;
        }

        $table ??= $query->getModel()->getTable();

        $commercialColumn = "{$table}.commercial_id";
        $livreurColumn = "{$table}.livreur_id";

        if ($user->isCommercial()) {
            $query->where($commercialColumn, $user->id);
        } elseif ($user->isLivreur()) {
            $query->where($livreurColumn, $user->id);
        }
    }
}
