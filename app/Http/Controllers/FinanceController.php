<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CashTransaction;
use App\Models\Commission;
use App\Models\CommercialObjective;
use App\Models\Expense;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $revenue = Order::where('status', OrderStatus::Livree)->sum('total');
        $expenses = Expense::sum('amount');
        $cashIn = CashTransaction::where('type', 'in')->sum('amount');
        $cashOut = CashTransaction::where('type', 'out')->sum('amount');

        return view('finance.index', [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'netProfit' => $revenue - $expenses,
            'treasury' => $cashIn - $cashOut,
            'recentExpenses' => Expense::latest()->limit(10)->get(),
            'recentTransactions' => CashTransaction::latest()->limit(10)->get(),
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        Expense::create([...$validated, 'user_id' => auth()->id()]);

        return back()->with('success', 'Dépense enregistrée.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
        ]);

        CashTransaction::create([...$validated, 'user_id' => auth()->id()]);

        return back()->with('success', 'Opération enregistrée.');
    }
}
