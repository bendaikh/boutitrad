<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportController extends Controller
{
    private const SECTIONS = ['purchases', 'sales', 'stock', 'charges'];

    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        return view('reports.index', [
            'summary' => $this->reports->summary(),
            'purchases' => $this->reports->purchases(),
            'sales' => $this->reports->sales(),
            'stockRows' => $this->reports->stockMovementsSummary(),
            'charges' => $this->reports->charges(),
        ]);
    }

    public function printSection(string $section): View
    {
        return view('reports.export-table', $this->sectionExportData($section));
    }

    public function exportPdfSection(string $section): Response
    {
        $data = $this->sectionExportData($section);
        $data['forPdf'] = true;

        return Pdf::loadView('reports.export-table', $data)
            ->setPaper('a4', 'landscape')
            ->download('rapport-'.$section.'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionExportData(string $section): array
    {
        if (! in_array($section, self::SECTIONS, true)) {
            throw new NotFoundHttpException();
        }

        $summary = $this->reports->summary();
        $money = fn (float $amount) => number_format($amount, 2, ',', ' ').' DH';

        return match ($section) {
            'purchases' => [
                'section' => $section,
                'title' => 'Achats',
                'headers' => ['Date', 'Réf Bn°', 'Fournisseur', 'Montant'],
                'alignments' => ['left', 'left', 'left', 'right'],
                'rows' => $this->reports->purchases()->map(fn (array $row) => [
                    $row['date'],
                    $row['reference'],
                    $row['supplier'],
                    $money($row['amount']),
                ])->all(),
                'totalLabel' => 'Total achats',
                'totalValue' => $money($summary['purchases_total']),
            ],
            'sales' => [
                'section' => $section,
                'title' => 'Ventes',
                'headers' => ['Date', 'Réf Bn°', 'Client', 'Commercial', 'Montant'],
                'alignments' => ['left', 'left', 'left', 'left', 'right'],
                'rows' => $this->reports->sales()->map(fn (array $row) => [
                    $row['date'],
                    $row['reference'],
                    $row['client'],
                    $row['commercial'],
                    $money($row['amount']),
                ])->all(),
                'totalLabel' => 'Total ventes',
                'totalValue' => $money($summary['sales_total']),
            ],
            'stock' => [
                'section' => $section,
                'title' => 'Mouvement Stock',
                'headers' => ['Catégorie', 'Produit', 'Qté Entrée', 'Qté Sortie', 'Stock', 'Statut'],
                'alignments' => ['left', 'left', 'center', 'center', 'center', 'center'],
                'rows' => $this->reports->stockMovementsSummary()->map(fn (array $row) => [
                    $row['category'],
                    $row['product'],
                    number_format($row['qty_in'], 0, ',', ' '),
                    number_format($row['qty_out'], 0, ',', ' '),
                    number_format($row['stock'], 0, ',', ' '),
                    $row['status'],
                ])->all(),
                'totalLabel' => 'Valeur stock',
                'totalValue' => $money($summary['stock_value']),
            ],
            'charges' => [
                'section' => $section,
                'title' => 'Charges',
                'headers' => ['Date', 'Libellé', 'Montant', 'Type Règl.'],
                'alignments' => ['left', 'left', 'right', 'center'],
                'rows' => $this->reports->charges()->map(fn (array $row) => [
                    $row['date'],
                    $row['label'],
                    $money($row['amount']),
                    $row['payment_type'],
                ])->all(),
                'totalLabel' => 'Total charges',
                'totalValue' => $money($summary['charges_total']),
            ],
        };
    }
}
