<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private OrderWorkflowService $workflow) {}

    public function partners(): View
    {
        return view('deliveries.partners', [
            'partners' => DeliveryPartner::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:delivery_partners,code'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'api_url' => ['nullable', 'url', 'max:255'],
            'api_token' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            DeliveryPartner::query()->update(['is_default' => false]);
        }

        DeliveryPartner::create([
            ...$validated,
            'is_active' => true,
            'is_default' => (bool) ($validated['is_default'] ?? false),
        ]);

        return back()->with('success', 'Partenaire ajouté.');
    }

    public function transport(Request $request): View
    {
        $user = auth()->user();

        $orders = Order::with(['client', 'livreur', 'deliveryPartner'])
            ->when($user->isLivreur(), fn ($q) => $q->where(function ($q) use ($user) {
                $q->where('livreur_id', $user->id)->orWhereNotNull('delivery_partner_id');
            }))
            ->whereIn('status', [
                OrderStatus::Confirmee,
                OrderStatus::EnPreparation,
                OrderStatus::Expediee,
                OrderStatus::Livree,
                OrderStatus::Retournee,
            ])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('sent_to_partner_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('deliveries.transport', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function showOrder(Order $order): View
    {
        $this->authorizeDeliveryOrder($order);
        $order->load(['client', 'commercial', 'deliveryPartner', 'items', 'statusHistories.user']);

        return view('deliveries.show', [
            'order' => $order,
        ]);
    }

    public function completeOrder(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeDeliveryOrder($order);

        $validated = $request->validate([
            'outcome' => ['required', 'in:delivered,returned'],
            'amount_collected' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->workflow->completeDelivery($order, $request->user(), $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['outcome' => $e->getMessage()]);
        }

        return redirect()
            ->route('deliveries.transport')
            ->with('success', $validated['outcome'] === 'delivered' ? 'Livraison confirmée.' : 'Retour enregistré.');
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

    private function authorizeDeliveryOrder(Order $order): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isLivreur()) {
            if ($order->livreur_id && $order->livreur_id !== $user->id) {
                abort(403);
            }

            if (! $order->delivery_partner_id && ! $order->livreur_id) {
                abort(403);
            }

            return;
        }

        abort(403);
    }
}
