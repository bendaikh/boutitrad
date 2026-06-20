<?php

namespace App\Http\Controllers;

use App\Enums\Bank;
use App\Enums\ChargeType;
use App\Enums\ExpenseTreasuryMode;
use App\Models\Expense;
use App\Services\ChargeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChargeController extends Controller
{
    public function __construct(private ChargeService $charges) {}

    public function index(Request $request): View
    {
        $editingExpense = null;

        if ($request->filled('selected')) {
            $editingExpense = $this->charges->find((int) $request->input('selected'));
        }

        [$dateFrom, $dateTo, $chargeMonth] = $this->charges->resolveMonthRange($request->input('charge_month'));
        $filters = $this->charges->activeFilters($request);

        return view('charges.index', [
            'chargeTypes' => ChargeType::cases(),
            'treasuryModes' => ExpenseTreasuryMode::cases(),
            'banks' => Bank::cases(),
            'expensesList' => $this->charges->expensesList($request),
            'editingExpense' => $editingExpense,
            'formActive' => $request->boolean('new') || $editingExpense !== null || $request->old('title') !== null,
            'selectedExpenseId' => $editingExpense?->id ?? ($request->filled('selected') ? (int) $request->input('selected') : null),
            'chargeMonth' => $chargeMonth,
            'chargeDateFrom' => $dateFrom,
            'chargeDateTo' => $dateTo,
            'chargeFilters' => $filters,
            'printUrl' => route('charges.print', $filters),
            'exportPdfUrl' => route('charges.export.pdf', $filters),
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        Expense::create([
            ...$validated,
            'category' => ChargeType::from($validated['charge_type'])->label(),
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('charges.index', $this->charges->activeFilters($request))
            ->with('success', 'Charge enregistrée.');
    }

    public function updateExpense(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        $expense->update([
            ...$validated,
            'category' => ChargeType::from($validated['charge_type'])->label(),
        ]);

        return redirect()
            ->route('charges.index', array_merge(
                $this->charges->activeFilters($request),
                ['selected' => $expense->id],
            ))
            ->with('success', 'Charge modifiée.');
    }

    public function destroyExpense(Request $request, Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('charges.index', $this->charges->activeFilters($request))
            ->with('success', 'Charge supprimée.');
    }

    public function print(Request $request): View
    {
        return view('charges.export', [
            'expenses' => $this->charges->allExpenses($request),
            'chargeMonth' => $this->charges->resolveMonthRange($request->input('charge_month'))[2],
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $chargeMonth = $this->charges->resolveMonthRange($request->input('charge_month'))[2];
        $expenses = $this->charges->allExpenses($request);
        $forPdf = true;

        return Pdf::loadView('charges.export', compact('expenses', 'chargeMonth', 'forPdf'))
            ->setPaper('a4', 'landscape')
            ->download('charges-'.$chargeMonth.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExpense(Request $request): array
    {
        $treasuryMode = ExpenseTreasuryMode::from($request->input('treasury_mode', ExpenseTreasuryMode::Caisse->value));
        $requiresDetails = $treasuryMode->requiresInstrumentDetails();

        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'charge_type' => ['required', Rule::enum(ChargeType::class)],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'treasury_mode' => ['required', Rule::enum(ExpenseTreasuryMode::class)],
            'payment_number' => [$requiresDetails ? 'required' : 'nullable', 'string', 'max:100'],
            'bank' => [$requiresDetails ? 'required' : 'nullable', Rule::enum(Bank::class)],
            'drawer_name' => [$requiresDetails ? 'required' : 'nullable', 'string', 'max:255'],
            'instrument_date' => [$requiresDetails ? 'required' : 'nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $requiresDetails) {
            $validated['payment_number'] = null;
            $validated['bank'] = null;
            $validated['drawer_name'] = null;
            $validated['instrument_date'] = null;
        }

        return $validated;
    }
}
