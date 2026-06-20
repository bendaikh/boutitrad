<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\OrderItem;
use App\Models\User;
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
                    $q->whereHas('client', function ($cq) use ($ville) {
                        if (ctype_digit($ville)) {
                            $cq->where('city_id', (int) $ville);
                        } else {
                            $cq->where('city', 'like', "%{$ville}%");
                        }
                    });
                }

                if ($request->filled('status')) {
                    $q->where('status', $request->status);
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

    public function statuses(): array
    {
        return OrderStatus::cases();
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
