<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesBalanceService
{
    public function filteredItems(Request $request, User $user): LengthAwarePaginator
    {
        return $this->baseQuery($request, $user)
            ->paginate(25)
            ->withQueryString();
    }

    public function baseQuery(Request $request, User $user): Builder
    {
        return OrderItem::query()
            ->with([
                'order.client',
                'order.commercial',
            ])
            ->whereHas('order', function ($q) use ($request, $user) {
                if ($user->isCommercial()) {
                    $q->where('commercial_id', $user->id);
                }

                if ($request->filled('date_from')) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                }

                if ($request->filled('reference')) {
                    $q->where('reference', 'like', '%'.trim($request->reference).'%');
                }

                if ($request->filled('client')) {
                    $term = trim($request->client);
                    $q->whereHas('client', function ($cq) use ($term) {
                        $cq->where('name', 'like', "%{$term}%");
                        $numericId = preg_replace('/\D/', '', $term);
                        if ($numericId !== '') {
                            $cq->orWhere('id', (int) $numericId);
                        }
                    });
                }

                if ($request->filled('ville')) {
                    $ville = trim($request->ville);
                    $q->whereHas('client', fn ($cq) => $cq->where('city', 'like', "%{$ville}%"));
                }
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.*')
            ->orderByDesc('orders.created_at')
            ->orderByDesc('order_items.id');
    }

    public function stats(Request $request, User $user): array
    {
        $orderIds = $this->baseQuery($request, $user)
            ->distinct()
            ->pluck('order_id');

        $orders = Order::query()->whereIn('id', $orderIds)->get();

        return [
            'orders_count' => $orders->count(),
            'orders_total' => $orders->sum(fn (Order $order) => $order->orderAmount()),
            'orders_balance' => $orders->sum(fn (Order $order) => $order->balanceDue()),
        ];
    }

    public function allItems(Request $request, User $user): Collection
    {
        return $this->baseQuery($request, $user)->get();
    }
}
