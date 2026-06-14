<?php

namespace App\Http\Controllers;

use App\Enums\Bank;
use App\Enums\PaymentMode;
use App\Enums\SettlementStatus;
use App\Models\OrderPayment;
use App\Enums\RegulationStatus;
use App\Services\PaymentService;
use App\Services\SalesBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesController extends Controller
{
    public function __construct(
        private SalesBalanceService $salesBalance,
        private PaymentService $paymentService,
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

    public function payments(): View
    {
        $user = auth()->user();
        $unpaidOrders = $this->paymentService->unpaidOrders($user);

        return view('sales.payments', [
            'payments' => $this->paymentService->paymentsList($user),
            'unpaidOrders' => $unpaidOrders,
            'treasuries' => $this->paymentService->activeTreasuries(),
            'paymentModes' => [
                PaymentMode::Esp,
                PaymentMode::Vir,
                PaymentMode::Vers,
                PaymentMode::Chq,
                PaymentMode::Eff,
                PaymentMode::Autre,
                PaymentMode::Comptant,
            ],
            'banks' => Bank::cases(),
            'settlementStatuses' => SettlementStatus::cases(),
            'regulationStatuses' => RegulationStatus::cases(),
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'order_id' => ['required', 'exists:orders,id'],
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'bank' => ['nullable', Rule::enum(Bank::class)],
            'payment_number' => ['nullable', 'string', 'max:100'],
            'settlement_status' => ['nullable', Rule::enum(SettlementStatus::class)],
            'regulation_status' => ['required', Rule::enum(RegulationStatus::class)],
            'drawer_name' => ['nullable', 'string', 'max:150'],
            'encashment_date' => ['nullable', 'date'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->paymentService->record($validated, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('sales.payments')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    public function updatePaymentStatus(Request $request, OrderPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'regulation_status' => ['required', Rule::enum(RegulationStatus::class)],
        ]);

        $this->paymentService->updateRegulationStatus(
            $payment,
            RegulationStatus::from($validated['regulation_status']),
            $request->user(),
        );

        return redirect()
            ->route('sales.payments')
            ->with('success', 'Statut du règlement mis à jour.');
    }
}
