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

    public function monthlySalesChart(?User $user = null): array
    {
        $year = now()->year;

        $query = Order::query()
            ->where('status', OrderStatus::Livree)
            ->whereYear('created_at', $year)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('SUM(total) as total')
            );

        $this->scopeOrdersForUser($query, $user);

        $rows = $query->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $result = [];

        foreach ($this->yearMonths($year) as $monthKey => $monthLabel) {
            $result[$monthLabel] = $rows->has($monthKey) ? (float) $rows[$monthKey]->total : 0;
        }

        return $result;
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
