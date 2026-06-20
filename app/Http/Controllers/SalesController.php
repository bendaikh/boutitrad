<?php

namespace App\Http\Controllers;

use App\Models\CommercialPayroll;
use App\Services\CommercialPayrollService;
use App\Services\SalesBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesController extends Controller
{
    public function __construct(
        private SalesBalanceService $salesBalance,
        private CommercialPayrollService $payrollService,
    ) {}

    public function balance(Request $request): View
    {
        $user = auth()->user();

        return view('sales.balance', [
            'items' => $this->salesBalance->filteredItems($request, $user),
            'stats' => $this->salesBalance->stats($request, $user),
        ]);
    }

    public function balancePrint(Request $request): View
    {
        $user = auth()->user();

        return view('sales.balance-export', [
            'items' => $this->salesBalance->allItems($request, $user),
            'stats' => $this->salesBalance->stats($request, $user),
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
    }

    public function balanceExportPdf(Request $request): Response
    {
        $user = auth()->user();
        $items = $this->salesBalance->allItems($request, $user);
        $stats = $this->salesBalance->stats($request, $user);
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $forPdf = true;

        return Pdf::loadView('sales.balance-export', compact('items', 'stats', 'dateFrom', 'dateTo', 'forPdf'))
            ->setPaper('a4', 'landscape')
            ->download('balance-'.now()->format('Y-m-d').'.pdf');
    }

    public function balanceExportExcel(Request $request): StreamedResponse
    {
        $user = auth()->user();
        $items = $this->salesBalance->allItems($request, $user);
        $filename = 'balance-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'Date',
                'N° bon',
                'ID client',
                'Nom client',
                'Ville livraison',
                'Désignation cmd',
                'Quantité',
                'Prix U',
                'Mnt total',
                'Mnt payé',
                'Mode paiement',
                'Solde',
                'Commercial',
            ], ';');

            foreach ($items as $item) {
                $order = $item->order;
                $client = $order->client;

                fputcsv($handle, [
                    $order->created_at->format('d/m/Y'),
                    $order->reference,
                    $client->formattedId(),
                    $client->name,
                    $client->city ?? '',
                    $item->product_name,
                    $item->quantity,
                    number_format($item->unit_price, 2, ',', ''),
                    number_format($item->total, 2, ',', ''),
                    number_format($order->paidAmount(), 2, ',', ''),
                    $order->payment_mode?->label() ?? '',
                    number_format($order->balanceDue(), 2, ',', ''),
                    $order->commercial?->name ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function payments(Request $request): View
    {
        $user = auth()->user();
        $editingPayroll = null;

        if ($request->filled('selected')) {
            $editingPayroll = $this->payrollService->findForUser((int) $request->input('selected'), $user);
        }

        return view('sales.payments', [
            'payrolls' => $this->payrollService->payrollsList($user, $request),
            'commercials' => $this->payrollService->commercials($user),
            'previewReference' => CommercialPayroll::previewReference(),
            'editingPayroll' => $editingPayroll,
            'formActive' => $request->boolean('new') || $editingPayroll !== null,
            'selectedPayrollId' => $editingPayroll?->id ?? ($request->filled('selected') ? (int) $request->input('selected') : null),
            'payrollFilters' => $this->payrollService->activeFilters($request),
        ]);
    }

    public function payrollStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commercial_id' => ['required', 'exists:users,id'],
            'pay_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'exclude_id' => ['nullable', 'integer', 'exists:commercial_payrolls,id'],
        ]);

        $user = $request->user();

        if ($user->isCommercial() && (int) $validated['commercial_id'] !== $user->id) {
            abort(403);
        }

        $stats = $this->payrollService->statsForCommercialMonth(
            (int) $validated['commercial_id'],
            $validated['pay_month'],
        );

        return response()->json([
            ...$stats,
            'duplicate' => $this->payrollService->duplicateExists(
                (int) $validated['commercial_id'],
                $validated['pay_month'],
                isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null,
            ),
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'pay_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'commercial_id' => ['required', 'exists:users,id'],
        ]);

        try {
            $payroll = $this->payrollService->record($validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['pay_month' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales.payments', array_merge(
                $this->payrollService->activeFilters($request),
                ['selected' => $payroll->id],
            ));
    }

    public function updatePayment(Request $request, CommercialPayroll $payroll): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'pay_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'commercial_id' => ['required', 'exists:users,id'],
        ]);

        try {
            $this->payrollService->update($payroll, $validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['pay_month' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales.payments', array_merge(
                $this->payrollService->activeFilters($request),
                ['selected' => $payroll->id],
            ));
    }

    public function destroyPayment(Request $request, CommercialPayroll $payroll): RedirectResponse
    {
        $this->payrollService->delete($payroll, $request->user());

        return redirect()
            ->route('sales.payments', $this->payrollService->activeFilters($request));
    }

    public function paymentsPrint(Request $request): View
    {
        $user = auth()->user();

        return view('sales.payments-export', [
            'payrolls' => $this->payrollService->allPayrolls($user, $request),
        ]);
    }

    public function paymentsExportPdf(Request $request): Response
    {
        $user = auth()->user();
        $payrolls = $this->payrollService->allPayrolls($user, $request);
        $forPdf = true;

        return Pdf::loadView('sales.payments-export', compact('payrolls', 'forPdf'))
            ->setPaper('a4', 'landscape')
            ->download('paie-commerciaux-'.now()->format('Y-m-d').'.pdf');
    }
}
