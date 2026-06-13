<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockReportService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private StockReportService $stockReport,
    ) {}

    public function index(Request $request): View
    {
        $products = $this->stockReport->filteredQuery($request)
            ->paginate(25)
            ->withQueryString();

        $stats = $this->stockReport->stats();
        $categories = Category::orderBy('name')->get();

        return view('stock.index', [
            'products' => $products,
            'soldStockQty' => $stats['soldStockQty'],
            'realStockQty' => $stats['realStockQty'],
            'lowStockQty' => $stats['lowStockQty'],
            'outOfStockQty' => $stats['outOfStockQty'],
            'categories' => $categories,
        ]);
    }

    public function print(Request $request): View
    {
        return view('stock.export', [
            'rows' => $this->stockReport->rowsForExport($request),
            'stats' => $this->stockReport->stats(),
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->stockReport->rowsForExport($request);
        $stats = $this->stockReport->stats();
        $forPdf = true;

        return Pdf::loadView('stock.export', compact('rows', 'stats', 'forPdf'))
            ->setPaper('a4', 'landscape')
            ->download('stock-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = $this->stockReport->rowsForExport($request);
        $filename = 'stock-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Réf prod', 'Désignation prod', 'Catégorie prod', 'Quantité prod', 'Statut prod', 'État prod'], ';');
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['sku'],
                    $row['name'],
                    $row['category'],
                    $row['quantity'],
                    $row['status'],
                    $row['etat'],
                ], ';');
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
