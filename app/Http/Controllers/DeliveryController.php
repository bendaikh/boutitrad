<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function partners(): View
    {
        return view('deliveries.partners');
    }

    public function transport(Request $request): View
    {
        $user = auth()->user();

        $orders = Order::with(['client', 'livreur'])
            ->when($user->isLivreur(), fn ($q) => $q->where('livreur_id', $user->id))
            ->whereIn('status', [
                OrderStatus::Confirmee,
                OrderStatus::EnPreparation,
                OrderStatus::Expediee,
                OrderStatus::Livree,
                OrderStatus::Retournee,
            ])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('deliveries.transport', [
            'orders' => $orders,
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function livreurs(): View
    {
        $livreurs = User::where('role', UserRole::Livreur)
            ->withCount(['deliveryOrders as active_deliveries_count' => fn ($q) => $q->whereIn('status', [
                OrderStatus::Confirmee,
                OrderStatus::EnPreparation,
                OrderStatus::Expediee,
            ])])
            ->latest()
            ->paginate(15);

        return view('deliveries.livreurs', [
            'livreurs' => $livreurs,
        ]);
    }
}
