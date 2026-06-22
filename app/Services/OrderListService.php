<?php

namespace App\Services;

use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\CathedisStatusMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderListService
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
                'order.deliveryPartner',
                'product.category',
            ])
            ->whereHas('order', function ($q) use ($request, $user) {
                if ($user->isCommercial()) {
                    $q->where('commercial_id', $user->id);
                }
                if ($user->isLivreur()) {
                    $q->where('livreur_id', $user->id);
                }

                if ($request->filled('delivery_ref')) {
                    $term = trim($request->delivery_ref);
                    $q->where('partner_tracking_ref', 'like', "%{$term}%");
                }

                if ($request->filled('date_from')) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                }

                if ($request->filled('ville')) {
                    $ville = trim($request->ville);
                    $q->whereHas('client', function ($cq) use ($ville) {
                        if (ctype_digit($ville)) {
                            $cq->where('city_id', (int) $ville);
                        } else {
                            $cq->where('city', 'like', "%{$ville}%");
                        }
                    });
                }

                if ($request->filled('cathedis_status')) {
                    $status = $request->cathedis_status;
                    if ($status === '__non_sync__') {
                        $q->whereNotNull('partner_tracking_ref')
                            ->where(function ($sq) {
                                $sq->whereNull('cathedis_status_code')
                                    ->orWhere('cathedis_status_code', '');
                            });
                    } else {
                        $q->where('cathedis_status_code', $status);
                    }
                }
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                if ($request->category_id === 'none') {
                    $q->where(function ($sub) {
                        $sub->whereNull('product_id')
                            ->orWhereHas('product', fn ($pq) => $pq->whereNull('category_id'));
                    });
                } else {
                    $q->whereHas('product', fn ($pq) => $pq->where('category_id', $request->category_id));
                }
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.*')
            ->orderByDesc('orders.created_at')
            ->orderByDesc('order_items.id');
    }

    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    /**
     * @return list<string>
     */
    public function cathedisStatuses(): array
    {
        $fromDb = Order::query()
            ->whereNotNull('cathedis_status_code')
            ->where('cathedis_status_code', '!=', '')
            ->distinct()
            ->orderBy('cathedis_status_code')
            ->pluck('cathedis_status_code')
            ->all();

        $defaults = array_filter(
            CathedisStatusMapper::filterOptions(),
            fn (string $status) => $status !== '__non_sync__',
        );

        return array_values(array_unique(array_merge($defaults, $fromDb)));
    }

    public function cities()
    {
        return City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
