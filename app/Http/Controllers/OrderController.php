<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\OrderListService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private OrderListService $orderList,
        private CommissionService $commissionService,
        private OrderWorkflowService $workflow,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        return view('orders.index', [
            'items' => $this->orderList->filteredItems($request, $user),
            'statuses' => $this->orderList->statuses(),
            'categories' => $this->orderList->categories(),
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $clients = Client::where('is_active', true)->orderBy('name')->get();

        return view('orders.create', [
            'clients' => $clients,
            'clientsData' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'formattedId' => $client->formattedId(),
                'name' => $client->name,
                'city' => $client->city ?? '',
                'commercial_id' => $client->commercial_id,
                'payment_mode' => $client->payment_mode?->value,
            ])->values(),
            'products' => Product::where('is_active', true)->where('quantity', '>', 0)->orderBy('name')->get(),
            'productsData' => Product::where('is_active', true)->where('quantity', '>', 0)->orderBy('name')->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'sale_price' => (float) $product->sale_price,
                'stock' => $product->quantity,
            ])->values(),
            'commercials' => User::where('role', UserRole::Commercial)->where('is_active', true)->get(),
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
            'paymentModes' => PaymentMode::cases(),
            'previewReference' => Order::generateReference(),
            'defaultDeliveryCost' => (float) Setting::get('delivery_fee', 0),
            'isCommercial' => $user->isCommercial(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'order_date' => 'nullable|date',
            'commercial_id' => 'nullable|exists:users,id',
            'livreur_id' => 'nullable|exists:users,id',
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'delivery_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'submit_action' => 'nullable|in:draft,submit',
        ]);

        $user = auth()->user();

        if ($user->isCommercial()) {
            $validated['commercial_id'] = $user->id;
            $validated['livreur_id'] = null;
        }

        $order = DB::transaction(function () use ($validated, $user) {
            $subtotal = 0;
            $items = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $deliveryCost = $validated['delivery_cost'] ?? 0;
            $total = max(0, $subtotal + $deliveryCost - $discount);

            $order = Order::create([
                'reference' => Order::generateReference(),
                'client_id' => $validated['client_id'],
                'commercial_id' => $validated['commercial_id'] ?? auth()->id(),
                'livreur_id' => $validated['livreur_id'] ?? null,
                'status' => OrderStatus::Nouvelle,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_cost' => $deliveryCost,
                'total' => $total,
                'payment_mode' => $validated['payment_mode'] ?? null,
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

            if (! empty($validated['order_date'])) {
                $order->created_at = $validated['order_date'];
                $order->save();
            }

            return $order;
        });

        $submitToAdmin = ($validated['submit_action'] ?? '') === 'submit';

        if ($submitToAdmin) {
            try {
                $this->workflow->submitToAdmin($order->fresh(), $user);
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('orders.show', $order)
                    ->with('success', 'Commande créée.')
                    ->withErrors(['workflow' => $e->getMessage()]);
            }

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Commande créée et envoyée à l\'admin pour validation.');
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $user->isCommercial()
                ? 'Commande enregistrée. Cliquez sur « Envoyer à l\'admin » pour la soumettre.'
                : 'Commande créée.');
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['client', 'commercial', 'livreur', 'deliveryPartner', 'items.product', 'statusHistories.user']);

        return view('orders.show', [
            'order' => $order,
            'statuses' => $this->workflow->allowedStatusesFor(auth()->user(), $order),
            'partners' => DeliveryPartner::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'commercials' => User::where('role', UserRole::Commercial)->where('is_active', true)->get(),
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
        ]);
    }

    public function submitToAdmin(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        try {
            $this->workflow->submitToAdmin($order, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Commande envoyée à l\'admin pour validation.');
    }

    public function validateAndDispatch(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $validated = $request->validate([
            'delivery_partner_id' => ['nullable', 'exists:delivery_partners,id'],
        ]);

        try {
            $this->workflow->validateAndDispatchToPartner(
                $order,
                auth()->user(),
                $validated['delivery_partner_id'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Commande validée et transmise au partenaire de livraison.');
    }

    public function rejectOrder(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        try {
            $this->workflow->rejectOrder($order, auth()->user(), $request->input('notes'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('success', 'Commande rejetée.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Seul l\'admin peut modifier le statut manuellement.');
        }

        $validated = $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'commercial_id' => 'nullable|exists:users,id',
            'livreur_id' => 'nullable|exists:users,id',
        ]);

        $status = OrderStatus::from($validated['status']);
        $previousStatus = $order->status;

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
        $order->refresh();

        $this->commissionService->syncAfterStatusChange($order, $previousStatus, $status);

        if ($status === OrderStatus::Livree && isset($validated['commercial_id'])) {
            $this->commissionService->grantForDeliveredOrder($order);
        }

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
