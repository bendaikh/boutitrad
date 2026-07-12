<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportController extends Controller
{
    private const SECTIONS = ['purchases', 'sales', 'stock', 'charges'];

    private const DATE_FILTER_KEYS = [
        'purchases_from',
        'purchases_to',
        'sales_from',
        'sales_to',
        'stock_from',
        'stock_to',
        'charges_from',
        'charges_to',
    ];

    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $filters = $this->activeFilters($request);

        $purchasesFrom = $request->query('purchases_from');
        $purchasesTo = $request->query('purchases_to');
        $salesFrom = $request->query('sales_from');
        $salesTo = $request->query('sales_to');
        $stockFrom = $request->query('stock_from');
        $stockTo = $request->query('stock_to');
        $chargesFrom = $request->query('charges_from');
        $chargesTo = $request->query('charges_to');

        $purchases = $this->reports->purchases($purchasesFrom, $purchasesTo);
        $sales = $this->reports->sales($salesFrom, $salesTo);
        $stockRows = $this->reports->stockMovementsSummary($stockFrom, $stockTo);
        $charges = $this->reports->charges($chargesFrom, $chargesTo);

        return view('reports.index', [
            'summary' => $this->reports->summary(),
            'reportFilters' => $filters,
            'purchases' => $purchases,
            'purchasesFrom' => $purchasesFrom,
            'purchasesTo' => $purchasesTo,
            'sales' => $sales,
            'salesAmountTotal' => round((float) $sales->sum('amount'), 2),
            'salesProfitTotal' => round((float) $sales->sum('profit'), 2),
            'salesFrom' => $salesFrom,
            'salesTo' => $salesTo,
            'stockRows' => $stockRows,
            'stockFrom' => $stockFrom,
            'stockTo' => $stockTo,
            'charges' => $charges,
            'chargesFrom' => $chargesFrom,
            'chargesTo' => $chargesTo,
        ]);
    }

    public function printSection(Request $request, string $section): View
    {
        return view('reports.export-table', $this->sectionExportData($section, $request));
    }

    public function exportPdfSection(Request $request, string $section): Response
    {
        $data = $this->sectionExportData($section, $request);
        $data['forPdf'] = true;

        return Pdf::loadView('reports.export-table', $data)
            ->setPaper('a4', 'landscape')
            ->download('rapport-'.$section.'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(Request $request): array
    {
        return array_filter(
            $request->only(self::DATE_FILTER_KEYS),
            fn ($value) => filled($value),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionExportData(string $section, Request $request): array
    {
        if (! in_array($section, self::SECTIONS, true)) {
            throw new NotFoundHttpException();
        }

        $money = fn (float $amount) => number_format($amount, 2, ',', ' ').' DH';

        return match ($section) {
            'purchases' => (function () use ($section, $money, $request) {
                $from = $request->query('purchases_from');
                $to = $request->query('purchases_to');
                $purchases = $this->reports->purchases($from, $to);

                return [
                    'section' => $section,
                    'title' => 'Achats',
                    'headers' => ['Date', 'Réf produit', 'Désignation', 'Fournisseur', 'Montant'],
                    'alignments' => ['left', 'left', 'left', 'left', 'right'],
                    'rows' => $purchases->map(fn (array $row) => [
                        $row['date'],
                        $row['reference'],
                        $row['product'],
                        $row['supplier'],
                        $money($row['amount']),
                    ])->all(),
                    'totalLabel' => 'Total achats',
                    'totalValue' => $money((float) $purchases->sum('amount')),
                ];
            })(),
            'sales' => (function () use ($section, $money, $request) {
                $from = $request->query('sales_from');
                $to = $request->query('sales_to');
                $sales = $this->reports->sales($from, $to);

                return [
                    'section' => $section,
                    'title' => 'Ventes',
                    'headers' => ['Date', 'Réf Bn°', 'Client', 'Commercial', 'Montant', 'Bénéfice'],
                    'alignments' => ['left', 'left', 'left', 'left', 'right', 'right'],
                    'rows' => $sales->map(fn (array $row) => [
                        $row['date'],
                        $row['reference'],
                        $row['client'],
                        $row['commercial'],
                        $money($row['amount']),
                        $money($row['profit']),
                    ])->all(),
                    'totalLabel' => 'Total bénéfice',
                    'totalValue' => $money((float) $sales->sum('profit')),
                ];
            })(),
            'stock' => (function () use ($section, $request) {
                $from = $request->query('stock_from');
                $to = $request->query('stock_to');
                $stockRows = $this->reports->stockMovementsSummary($from, $to);
                $stockValue = (float) $stockRows->sum(fn (array $row) => $row['stock']);

                return [
                    'section' => $section,
                    'title' => 'Mouvement Stock',
                    'headers' => ['Catégorie', 'Produit', 'Qté Entrée', 'Qté Sortie', 'Stock', 'Statut'],
                    'alignments' => ['left', 'left', 'center', 'center', 'center', 'center'],
                    'rows' => $stockRows->map(fn (array $row) => [
                        $row['category'],
                        $row['product'],
                        number_format($row['qty_in'], 0, ',', ' '),
                        number_format($row['qty_out'], 0, ',', ' '),
                        number_format($row['stock'], 0, ',', ' '),
                        $row['status'],
                    ])->all(),
                    'totalLabel' => 'Stock total (lignes filtrées)',
                    'totalValue' => number_format($stockValue, 0, ',', ' '),
                ];
            })(),
            'charges' => (function () use ($section, $money, $request) {
                $from = $request->query('charges_from');
                $to = $request->query('charges_to');
                $charges = $this->reports->charges($from, $to);

                return [
                    'section' => $section,
                    'title' => 'Charges',
                    'headers' => ['Date', 'Libellé', 'Montant', 'Type Règl.'],
                    'alignments' => ['left', 'left', 'right', 'center'],
                    'rows' => $charges->map(fn (array $row) => [
                        $row['date'],
                        $row['label'],
                        $money($row['amount']),
                        $row['payment_type'],
                    ])->all(),
                    'totalLabel' => 'Total charges',
                    'totalValue' => $money((float) $charges->sum('amount')),
                ];
            })(),
        };
    }
}
