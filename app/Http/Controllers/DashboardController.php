<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->isCommercial()) {
            return view('dashboard.commercial', $this->commercialDashboardData($user));
        }

        [$dateFrom, $dateTo] = $this->dashboard->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to'),
        );

        [$commercialDateFrom, $commercialDateTo, $commercialMonth] = $this->dashboard->resolveMonthRange(
            $request->input('commercial_month'),
        );

        return view('dashboard', [
            'stats' => $this->dashboard->stats($user),
            'alerts' => $this->dashboard->alerts($user),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'commercialMonth' => $commercialMonth,
            'commercialDateFrom' => $commercialDateFrom,
            'commercialDateTo' => $commercialDateTo,
            'orderLines' => $this->dashboard->orderLinesByDateRange($request, $user),
            'commercialState' => $this->dashboard->commercialStateByDateRange($commercialDateFrom, $commercialDateTo, $user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function commercialDashboardData(User $commercial): array
    {
        return [
            'commercial' => $commercial,
            'stats' => $this->dashboard->commercialStats($commercial),
            'orders' => $this->dashboard->commercialOrders($commercial),
            'stockProducts' => $this->dashboard->commercialStock(),
            'clients' => $this->dashboard->commercialClients($commercial),
        ];
    }
}
