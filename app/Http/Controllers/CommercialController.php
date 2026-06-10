<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\CommercialObjective;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class CommercialController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isCommercial()) {
            return $this->dashboard($user);
        }

        $commercials = User::where('role', UserRole::Commercial)
            ->where('is_active', true)
            ->withCount(['commercialOrders as delivered_orders_count' => fn ($q) => $q->where('status', OrderStatus::Livree)])
            ->get()
            ->map(function ($commercial) {
                $commercial->total_sales = Order::where('commercial_id', $commercial->id)
                    ->where('status', OrderStatus::Livree)
                    ->sum('total');

                return $commercial;
            });

        return view('commercials.index', compact('commercials'));
    }

    public function show(User $user): View
    {
        abort_unless($user->role === UserRole::Commercial, 404);

        if (auth()->user()->isCommercial() && auth()->id() !== $user->id) {
            abort(403);
        }

        return $this->dashboard($user);
    }

    private function dashboard(User $commercial): View
    {
        $orders = Order::where('commercial_id', $commercial->id)->latest()->limit(10)->get();
        $totalSales = Order::where('commercial_id', $commercial->id)->where('status', OrderStatus::Livree)->sum('total');
        $ordersCount = Order::where('commercial_id', $commercial->id)->count();
        $objectives = CommercialObjective::where('user_id', $commercial->id)->latest()->limit(3)->get();
        $commissions = Commission::where('user_id', $commercial->id)->latest()->limit(10)->get();
        $totalCommissions = Commission::where('user_id', $commercial->id)->sum('amount');

        return view('commercials.show', compact(
            'commercial', 'orders', 'totalSales', 'ordersCount',
            'objectives', 'commissions', 'totalCommissions'
        ));
    }
}
