<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\CashTransaction;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Order;
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

    public function orderStatusChart(?User $user = null): array
    {
        $query = Order::query()->select('status', DB::raw('count(*) as total'));
        $this->scopeOrdersForUser($query, $user);

        return $query->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [
                OrderStatus::from($status)->label() => $total,
            ])
            ->toArray();
    }

    public function orderDistributionChart(?User $user = null): array
    {
        $pendingStatuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::activeStatuses());

        $query = Order::query()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Livree->value."' THEN 1 ELSE 0 END) as validated"),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", $pendingStatuses)."') THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Annulee->value."' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Retournee->value."' THEN 1 ELSE 0 END) as returns"),
            );

        $this->scopeOrdersForUser($query, $user);

        $rows = $query->groupBy('month')->orderBy('month')->get();

        $monthLabels = ['01' => 'JAN', '02' => 'FÉV', '03' => 'MAR', '04' => 'AVR', '05' => 'MAI', '06' => 'JUN',
            '07' => 'JUL', '08' => 'AOÛ', '09' => 'SEP', '10' => 'OCT', '11' => 'NOV', '12' => 'DÉC'];

        $labels = [];
        $validated = [];
        $pending = [];
        $cancelled = [];
        $returns = [];

        foreach ($rows as $row) {
            $monthNum = substr($row->month, 5, 2);
            $labels[] = $monthLabels[$monthNum] ?? $row->month;
            $validated[] = (int) $row->validated;
            $pending[] = (int) $row->pending;
            $cancelled[] = (int) $row->cancelled;
            $returns[] = (int) $row->returns;
        }

        return compact('labels', 'validated', 'pending', 'cancelled', 'returns');
    }

    public function monthlySalesChart(?User $user = null): array
    {
        $query = Order::query()
            ->where('status', OrderStatus::Livree)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total) as total')
            );

        $this->scopeOrdersForUser($query, $user);

        return $query->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
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

    public function livreurPerformance(?User $user = null): array
    {
        if ($user?->isLivreur()) {
            return Order::where('livreur_id', $user->id)
                ->where('status', OrderStatus::Livree)
                ->select('livreur_id', DB::raw('COUNT(*) as count'))
                ->groupBy('livreur_id')
                ->get()
                ->map(fn ($row) => [
                    'name' => $user->name,
                    'count' => $row->count,
                ])
                ->toArray();
        }

        return Order::where('status', OrderStatus::Livree)
            ->whereNotNull('livreur_id')
            ->select('livreur_id', DB::raw('COUNT(*) as count'))
            ->groupBy('livreur_id')
            ->with('livreur:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->livreur?->name ?? 'N/A',
                'count' => $row->count,
            ])
            ->toArray();
    }

    public function recentOrders(?User $user = null, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $query = Order::with(['client', 'commercial', 'livreur'])->latest();
        $this->scopeOrdersForUser($query, $user);

        return $query->limit($limit)->get();
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
            $pending = Order::where('status', OrderStatus::Nouvelle)->count();
            if ($pending > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "{$pending} commande(s) en attente de validation",
                ];
            }
        }

        return $alerts;
    }

    private function scopeOrdersForUser($query, ?User $user): void
    {
        if (! $user) {
            return;
        }

        if ($user->isCommercial()) {
            $query->where('commercial_id', $user->id);
        } elseif ($user->isLivreur()) {
            $query->where('livreur_id', $user->id);
        }
    }
}
