<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockReportService
{
    public function filteredQuery(Request $request): Builder
    {
        return Product::query()
            ->with('category')
            ->when($request->search, fn ($q, $s) => $q->where(function ($query) use ($s) {
                $query->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            }))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                if ($request->category_id === 'none') {
                    $q->whereNull('category_id');
                } else {
                    $q->where('category_id', $request->category_id);
                }
            })
            ->when($request->status === 'faible', fn ($q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity'))
            ->when($request->status === 'rupture', fn ($q) => $q->where('quantity', '<=', 0))
            ->when($request->status === 'dispo', fn ($q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '>', 'min_quantity'))
            ->when($request->etat === 'actif', fn ($q) => $q->where('is_active', true))
            ->when($request->etat === 'inactif', fn ($q) => $q->where('is_active', false))
            ->orderBy('name');
    }

    public function stats(): array
    {
        return [
            'soldStockQty' => (int) OrderItem::query()
                ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Livree))
                ->sum('quantity'),
            'realStockQty' => (int) Product::query()->sum('quantity'),
            'lowStockQty' => Product::query()
                ->where('quantity', '>', 0)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->count(),
            'outOfStockQty' => Product::query()
                ->where('quantity', '<=', 0)
                ->count(),
        ];
    }

    public function rowsForExport(Request $request): array
    {
        return $this->filteredQuery($request)
            ->get()
            ->map(fn (Product $product) => [
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category?->name ?? '—',
                'quantity' => $product->quantity,
                'status' => $product->stockStatusLabel(),
                'etat' => $product->is_active ? 'Actif' : 'Inactif',
            ])
            ->all();
    }
}
