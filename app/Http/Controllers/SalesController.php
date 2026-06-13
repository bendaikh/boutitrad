<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\CashTransaction;
use App\Models\Client;
use App\Models\Order;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function balance(): View
    {
        return view('sales.balance', [
            'totalSales' => Order::where('status', OrderStatus::Livree)->sum('total'),
            'pendingAmount' => Order::whereNotIn('status', [OrderStatus::Livree, OrderStatus::Annulee])->sum('total'),
            'clientsBalance' => Client::sum('balance'),
            'clientsDebt' => Client::where('balance', '<', 0)->sum('balance'),
        ]);
    }

    public function payments(): View
    {
        return view('sales.payments', [
            'transactions' => CashTransaction::where('type', 'in')->latest()->paginate(15),
        ]);
    }
}
