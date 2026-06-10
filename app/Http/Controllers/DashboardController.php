<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'stats' => $this->dashboard->stats($user),
            'orderStatusChart' => $this->dashboard->orderStatusChart($user),
            'monthlySalesChart' => $this->dashboard->monthlySalesChart($user),
            'commercialPerformance' => $this->dashboard->commercialPerformance($user),
            'livreurPerformance' => $this->dashboard->livreurPerformance($user),
            'recentOrders' => $this->dashboard->recentOrders($user),
            'alerts' => $this->dashboard->alerts($user),
        ]);
    }
}
