<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\CommercialPayroll;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function stats(?User $user = null): array
    {
        $ordersQuery = Order::query();
        $this->scopeOrdersForUser($ordersQuery, $user);

        $confirmedRevenue = (clone $ordersQuery)
            ->whereIn('status', $this->confirmedStatuses())
            ->sum('total');

        $totalOrders = (clone $ordersQuery)->count();

        $expenses = Expense::query()->sum('amount');
        $commercialPayments = CommercialPayroll::query()->sum('amount_to_pay');
        $netProfit = $confirmedRevenue - $commercialPayments - $expenses;

        return [
            'revenue' => $confirmedRevenue,
            'total_orders' => $totalOrders,
            'expenses' => $expenses,
            'commercial_payroll_total' => $commercialPayments,
            'net_profit' => $netProfit,
            'clients_count' => Client::where('is_active', true)->count(),
            'products_count' => Product::where('is_active', true)->count(),
            'low_stock_count' => Product::whereColumn('quantity', '<=', 'min_quantity')->count(),
        ];
    }

    public function orderLinesByDateRange(Request $request, ?User $user, int $perPage = 25): LengthAwarePaginator
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to'),
        );

        $query = OrderItem::query()
            ->with([
                'order.client.cityRecord',
                'order.commercial',
                'product.category',
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.*')
            ->whereDate('orders.created_at', '>=', $dateFrom)
            ->whereDate('orders.created_at', '<=', $dateTo)
            ->orderByDesc('orders.created_at')
            ->orderByDesc('order_items.id');

        $this->scopeOrdersForUser($query, $user, 'orders');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, array{
     *     date: string,
     *     commercial_name: string,
     *     ventes_confir: int,
     *     ventes_annulee: int,
     *     ventes_retour: int,
     *     chiffre_confir: float,
     * }>
     */
    public function commercialStateByDateRange(string $dateFrom, string $dateTo, ?User $user): Collection
    {
        $confirmedStatuses = $this->confirmedStatuses();

        $query = Order::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereNotNull('commercial_id')
            ->select(
                DB::raw('DATE(created_at) as order_date'),
                'commercial_id',
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", $confirmedStatuses)."') THEN 1 ELSE 0 END) as ventes_confir"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Annulee->value."' THEN 1 ELSE 0 END) as ventes_annulee"),
                DB::raw("SUM(CASE WHEN status = '".OrderStatus::Retournee->value."' THEN 1 ELSE 0 END) as ventes_retour"),
                DB::raw("SUM(CASE WHEN status IN ('".implode("','", $confirmedStatuses)."') THEN total ELSE 0 END) as chiffre_confir"),
            )
            ->groupBy('order_date', 'commercial_id')
            ->with('commercial:id,name')
            ->orderByDesc('order_date')
            ->orderBy('commercial_id');

        $this->scopeOrdersForUser($query, $user);

        return $query->get()->map(fn ($row) => [
            'date' => \Carbon\Carbon::parse($row->order_date)->format('d/m/Y'),
            'commercial_name' => $row->commercial?->name ?? '—',
            'ventes_confir' => (int) $row->ventes_confir,
            'ventes_annulee' => (int) $row->ventes_annulee,
            'ventes_retour' => (int) $row->ventes_retour,
            'chiffre_confir' => (float) $row->chiffre_confir,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolveDateRange(?string $dateFrom, ?string $dateTo): array
    {
        if (! filled($dateFrom) && ! filled($dateTo)) {
            return [
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
            ];
        }

        $from = $this->parseDate($dateFrom) ?? now()->startOfMonth()->toDateString();
        $to = $this->parseDate($dateTo) ?? now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    public function resolveMonthRange(?string $month): array
    {
        $parsedMonth = $this->parseMonth($month) ?? now()->startOfMonth();

        return [
            $parsedMonth->copy()->startOfMonth()->toDateString(),
            $parsedMonth->copy()->endOfMonth()->toDateString(),
            $parsedMonth->format('Y-m'),
        ];
    }

    private function parseMonth(?string $value): ?\Carbon\Carbon
    {
        if (! filled($value) || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
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

    /**
     * @return array{orders_count: int, clients_count: int, products_count: int, real_stock_qty: int}
     */
    public function commercialStats(User $commercial): array
    {
        return [
            'orders_count' => Order::query()->where('commercial_id', $commercial->id)->count(),
            'clients_count' => Client::query()->where('commercial_id', $commercial->id)->count(),
            'products_count' => Product::query()->where('is_active', true)->count(),
            'real_stock_qty' => (int) Product::query()->sum('quantity'),
        ];
    }

    public function commercialOrders(User $commercial, int $limit = 15): \Illuminate\Support\Collection
    {
        return Order::query()
            ->with('client')
            ->where('commercial_id', $commercial->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function commercialClients(User $commercial, int $limit = 15): \Illuminate\Support\Collection
    {
        return Client::query()
            ->where('commercial_id', $commercial->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function commercialStock(int $limit = 15): \Illuminate\Support\Collection
    {
        return Product::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();
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

    /**
     * @return list<string>
     */
    private function confirmedStatuses(): array
    {
        return [
            OrderStatus::Confirmee->value,
            OrderStatus::EnPreparation->value,
            OrderStatus::Expediee->value,
            OrderStatus::Livree->value,
        ];
    }
}
