<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        try {
            $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable) {
            $monthDate = now()->startOfMonth();
            $selectedMonth = $monthDate->format('Y-m');
        }

        $year = (int) $monthDate->year;
        $month = (int) $monthDate->month;

        return view('dashboard', [
            'stats' => $this->dashboard->stats($user),
            'orderDistributionChart' => $this->dashboard->orderDistributionChart($user),
            'alerts' => $this->dashboard->alerts($user),
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $this->dashboard->monthLabel($year, $month),
            'commercialSalesByMonth' => $this->dashboard->commercialSalesByMonth($user, $year, $month),
            'topProductsByMonth' => $this->dashboard->topProductsByMonth($user, $year, $month),
            'activeCitiesByMonth' => $this->dashboard->activeCitiesByMonth($user, $year, $month),
        ]);
    }
}
