<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request): View
    {
        $products = Product::with(['category'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->low_stock, fn ($q) => $q->whereColumn('quantity', '<=', 'min_quantity'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalValue = Product::selectRaw('SUM(quantity * purchase_price) as value')->value('value') ?? 0;
        $lowStockCount = Product::whereColumn('quantity', '<=', 'min_quantity')->count();

        return view('stock.index', compact('products', 'totalValue', 'lowStockCount'));
    }

    public function movements(Request $request): View
    {
        $movements = StockMovement::with(['product', 'user'])
            ->latest()
            ->paginate(20);

        return view('stock.movements', compact('movements'));
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entree,sortie,ajustement,inventaire',
            'quantity' => 'required|integer|min:0',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $this->stockService->adjustStock(
            $product,
            StockMovementType::from($validated['type']),
            (int) $validated['quantity'],
            $validated['reference'] ?? null,
            $validated['notes'] ?? null,
            auth()->id()
        );

        return back()->with('success', 'Mouvement de stock enregistré.');
    }
}
