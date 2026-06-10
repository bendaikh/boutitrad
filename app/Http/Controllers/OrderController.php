<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $orders = Order::with(['client', 'commercial', 'livreur'])
            ->when($user->isCommercial(), fn ($q) => $q->where('commercial_id', $user->id))
            ->when($user->isLivreur(), fn ($q) => $q->where('livreur_id', $user->id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('quantity', '>', 0)->orderBy('name')->get(),
            'commercials' => User::where('role', UserRole::Commercial)->where('is_active', true)->get(),
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'commercial_id' => 'nullable|exists:users,id',
            'livreur_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $items = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $lineTotal = $product->sale_price * $item['quantity'];
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->sale_price,
                    'total' => $lineTotal,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $total = max(0, $subtotal - $discount);

            $order = Order::create([
                'reference' => Order::generateReference(),
                'client_id' => $validated['client_id'],
                'commercial_id' => $validated['commercial_id'] ?? auth()->id(),
                'livreur_id' => $validated['livreur_id'] ?? null,
                'status' => OrderStatus::Nouvelle,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => OrderStatus::Nouvelle->value,
                'notes' => 'Commande créée',
                'user_id' => auth()->id(),
            ]);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', 'Commande créée.');
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['client', 'commercial', 'livreur', 'items.product', 'statusHistories.user']);

        return view('orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
            'commercials' => User::where('role', UserRole::Commercial)->where('is_active', true)->get(),
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'commercial_id' => 'nullable|exists:users,id',
            'livreur_id' => 'nullable|exists:users,id',
        ]);

        $status = OrderStatus::from($validated['status']);

        $updates = ['status' => $status];

        if (isset($validated['commercial_id'])) {
            $updates['commercial_id'] = $validated['commercial_id'];
        }
        if (isset($validated['livreur_id'])) {
            $updates['livreur_id'] = $validated['livreur_id'];
        }
        if ($status === OrderStatus::Confirmee) {
            $updates['validated_at'] = now();
        }
        if ($status === OrderStatus::Livree) {
            $updates['delivered_at'] = now();
        }
        if ($status === OrderStatus::Annulee) {
            $updates['cancelled_at'] = now();
        }

        $order->update($updates);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status->value,
            'notes' => $validated['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function invoice(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['client', 'items', 'commercial']);

        return view('orders.invoice', compact('order'));
    }

    private function authorizeOrder(Order $order): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isCommercial() && $order->commercial_id !== $user->id) {
            abort(403);
        }

        if ($user->isLivreur() && $order->livreur_id !== $user->id) {
            abort(403);
        }
    }
}
