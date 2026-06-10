<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'salesTotal' => Order::where('status', OrderStatus::Livree)->sum('total'),
            'ordersCount' => Order::count(),
            'clientsCount' => Client::count(),
            'productsCount' => Product::count(),
            'expensesTotal' => Expense::sum('amount'),
            'commercialsCount' => User::where('role', UserRole::Commercial)->count(),
            'livreursCount' => User::where('role', UserRole::Livreur)->count(),
            'stockValue' => Product::selectRaw('SUM(quantity * purchase_price) as v')->value('v') ?? 0,
            'ordersByStatus' => Order::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'topProducts' => DB::table('order_items')
                ->select('product_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total) as revenue'))
                ->groupBy('product_name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get(),
        ]);
    }
}
