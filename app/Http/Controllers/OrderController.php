<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\ProspectionSource;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Client;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\ImageUpload;
use App\Services\CommissionService;
use App\Services\CathedisDispatchException;
use App\Services\OrderListService;
use App\Services\OrderStockService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\PhoneHelper;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private OrderListService $orderList,
        private CommissionService $commissionService,
        private OrderWorkflowService $workflow,
        private OrderStockService $orderStock,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        return view('orders.index', [
            'items' => $this->orderList->filteredItems($request, $user),
            'statuses' => $this->orderList->cathedisStatuses(),
            'categories' => $this->orderList->categories(),
            'cities' => $this->orderList->cities(),
        ]);
    }

    public function create(): View
    {
        return view('orders.create', $this->orderFormViewData());
    }

    public function edit(Order $order): View
    {
        $this->authorizeOrder($order);
        abort_unless($order->canBeEditedInForm(), 403, $this->orderEditDeniedMessage($order));

        return view('orders.create', $this->orderFormViewData($order));
    }

    /**
     * @return array<string, mixed>
     */
    private function orderFormViewData(?Order $order = null): array
    {
        $user = auth()->user();
        $clients = Client::where('is_active', true)->with('cityRecord')->orderBy('name')->get();

        $initialItems = old('items');

        if ($initialItems === null && $order) {
            $order->loadMissing('items.product');
            $initialItems = $order->items->map(fn (OrderItem $item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'product_image' => $item->product_image,
                'product_image_url' => $item->productImageUrl(),
                'remark' => $item->remark,
            ])->values()->all();
        }

        if ($order) {
            $order->loadMissing('items');
        }

        $reservedQuantities = $order
            ? $order->items->groupBy('product_id')->map(fn ($items) => (int) $items->sum('quantity'))
            : collect();

        $orderProductIds = $order
            ? $order->items->pluck('product_id')->filter()->unique()
            : collect();

        $activeProducts = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($orderProductIds) {
                $query->where('quantity', '>', 0);

                if ($orderProductIds->isNotEmpty()) {
                    $query->orWhereIn('id', $orderProductIds);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'order' => $order,
            'clients' => $clients,
            'clientsData' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'formattedId' => $client->formattedId(),
                'name' => $client->name,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->deliveryCityName(),
                'city_id' => $client->city_id,
                'delivery_cost' => $client->suggestedDeliveryCost(),
                'commercial_id' => $client->commercial_id,
                'payment_mode' => $client->payment_mode?->value,
            ])->values(),
            'products' => $activeProducts,
            'productsData' => $activeProducts->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'sale_price' => (float) $product->sale_price,
                'stock' => (int) $product->quantity + (int) ($reservedQuantities[$product->id] ?? 0),
                'min_stock' => (int) ($product->min_quantity ?: 5),
            ])->values(),
            'commercials' => User::where('role', UserRole::Commercial)->where('is_active', true)->get(),
            'livreurs' => User::where('role', UserRole::Livreur)->where('is_active', true)->get(),
            'paymentModes' => PaymentMode::cases(),
            'previewReference' => $order?->reference ?? Order::previewReference(),
            'previewDeliveryReference' => $order?->partner_tracking_ref,
            'defaultDeliveryCost' => (float) Setting::get('delivery_fee', 0),
            'isCommercial' => $user->isCommercial(),
            'initialItems' => $initialItems ?? [],
            'cities' => $this->cities(),
            'citiesData' => $this->citiesData(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, delivery_cost: float}>
     */
    private function citiesData()
    {
        $pack = config('cathedis_cities.default_pack', 'silver');

        return $this->cities()->map(fn (City $city) => [
            'id' => $city->id,
            'name' => $city->name,
            'delivery_cost' => $city->deliveryCost($pack),
        ])->values();
    }

    private function cities()
    {
        return City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOrderRequest(Request $request, bool $isCommercial = false): array
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'manual_client' => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_address' => 'nullable|string|max:500',
            'city_id' => 'nullable|exists:cities,id',
            'order_date' => 'nullable|date',
            'commercial_id' => 'nullable|exists:users,id',
            'livreur_id' => 'nullable|exists:users,id',
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'shipping_remark' => 'nullable|string|max:2000',
            'discount' => 'nullable|numeric|min:0',
            'delivery_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.product_image' => ImageUpload::RULE,
            'items.*.existing_product_image' => 'nullable|string|max:255',
            'items.*.remark' => 'nullable|string|max:2000',
            'submit_action' => 'nullable|in:draft,submit',
        ]);

        foreach (array_keys($validated['items']) as $index) {
            ImageUpload::assertValidUpload($request, "items.{$index}.product_image");
        }

        if ($request->boolean('manual_client')) {
            $validated['client_id'] = null;
        }

        if (empty($validated['client_id'])) {
            $request->validate([
                'client_name' => 'required|string|max:255',
                'client_phone' => 'required|string|max:50',
                'client_address' => 'required|string|max:500',
                'city_id' => 'required|exists:cities,id',
            ]);
            $validated['client_name'] = $request->input('client_name');
            $validated['client_phone'] = $request->input('client_phone');
            $validated['client_address'] = $request->input('client_address');
            $validated['city_id'] = $request->input('city_id');
        }

        $this->assertValidClientPhone($validated, $request);

        if ($isCommercial && ($validated['submit_action'] ?? '') === 'submit') {
            foreach (array_keys($validated['items']) as $index) {
                $hasNewPhoto = $request->hasFile("items.{$index}.product_image");
                $hasExistingPhoto = filled($request->input("items.{$index}.existing_product_image"));

                if (! $hasNewPhoto && ! $hasExistingPhoto) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_image" => 'Ajoutez une photo pour chaque produit.',
                    ]);
                }

                if (! filled(trim((string) $request->input("items.{$index}.remark", '')))) {
                    throw ValidationException::withMessages([
                        "items.{$index}.remark" => 'Saisissez une NB pour chaque produit.',
                    ]);
                }
            }
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertValidClientPhone(array $validated, Request $request): void
    {
        $phone = $validated['client_phone'] ?? null;

        if (! filled($phone) && ! empty($validated['client_id'])) {
            $phone = Client::query()->whereKey($validated['client_id'])->value('phone');
        }

        if (! PhoneHelper::hasTenDigits($phone)) {
            throw ValidationException::withMessages([
                'client_phone' => 'Le téléphone client doit contenir exactement 10 chiffres.',
            ]);
        }
    }

    private function resolveClientId(array $validated, User $user): int
    {
        $clientData = $this->clientAttributesFromOrder($validated, $user);

        if (! empty($validated['client_id'])) {
            $client = Client::findOrFail($validated['client_id']);
            $client->update($clientData);

            return $client->id;
        }

        if (! empty($validated['client_phone'])) {
            $normalizedPhone = PhoneHelper::normalize($validated['client_phone']);
            $existing = Client::query()
                ->where('phone', $normalizedPhone)
                ->orWhere('phone', $validated['client_phone'])
                ->first();

            if ($existing) {
                $existing->update($clientData);

                return $existing->id;
            }
        }

        $client = Client::create(array_merge($clientData, [
            'prospection' => ProspectionSource::Terrain,
            'is_active' => true,
        ]));

        return $client->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function clientAttributesFromOrder(array $validated, User $user): array
    {
        $city = ! empty($validated['city_id'])
            ? City::find($validated['city_id'])
            : null;

        $commercialId = $validated['commercial_id'] ?? ($user->isCommercial() ? $user->id : null);

        $attributes = [
            'name' => $validated['client_name'] ?? null,
            'phone' => PhoneHelper::normalize($validated['client_phone'] ?? null),
            'address' => $validated['client_address'] ?? null,
            'city_id' => $city?->id,
            'city' => $city?->name,
            'commercial_id' => $commercialId,
        ];

        if (filled($validated['payment_mode'] ?? null)) {
            $attributes['payment_mode'] = PaymentMode::from($validated['payment_mode']);
        }

        return array_filter(
            $attributes,
            fn ($value) => $value !== null && $value !== ''
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $this->validateOrderRequest($request, $user->isCommercial());

        if ($user->isCommercial()) {
            $validated['commercial_id'] = $user->id;
            $validated['livreur_id'] = null;
        }

        $this->orderStock->assertItemsAvailable($validated['items']);

        $order = DB::transaction(function () use ($validated, $user, $request) {
            $clientId = $this->resolveClientId($validated, $user);
            $subtotal = 0;
            $items = [];

            foreach ($validated['items'] as $index => $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'product_image' => $this->resolveItemProductImage($request, $index, $item),
                    'remark' => filled($item['remark'] ?? null) ? trim((string) $item['remark']) : null,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $deliveryCost = $validated['delivery_cost'] ?? 0;
            $total = max(0, $subtotal + $deliveryCost - $discount);

            $order = Order::create([
                'reference' => Order::generateReference(),
                'client_id' => $clientId,
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
                'shipping_remark' => $validated['shipping_remark'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_image' => $item['product_image'],
                    'remark' => $item['remark'] ?? null,
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

            $this->orderStock->deductForOrder($order->fresh(['items.product']), $user);

            return $order;
        });

        $submitToAdmin = ! $user->isCommercial() && ($validated['submit_action'] ?? '') === 'submit';

        if ($submitToAdmin) {
            try {
                $this->workflow->submitToAdmin($order->fresh(), $user);
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('orders.bon', $order)
                    ->with('success', 'Commande créée.')
                    ->withErrors(['workflow' => $e->getMessage()]);
            }

            return redirect()
                ->route('orders.bon', $order)
                ->with('success', 'Commande créée et envoyée à l\'admin pour validation.');
        }

        return redirect()
            ->route('orders.bon', $order)
            ->with('success', $user->isCommercial()
                ? 'Commande enregistrée. Les informations client ont été synchronisées avec la fiche clients.'
                : 'Commande créée. Les informations client ont été synchronisées avec la fiche clients.');
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        abort_unless($order->canBeEditedInForm(), 403, $this->orderEditDeniedMessage($order));

        $user = auth()->user();
        $validated = $this->validateOrderRequest($request, $user->isCommercial());

        if ($user->isCommercial()) {
            $validated['commercial_id'] = $user->id;
            $validated['livreur_id'] = null;
        }

        $this->orderStock->assertItemsAvailable($validated['items'], $order);

        DB::transaction(function () use ($validated, $order, $user, $request) {
            $previousItems = $order->items()->get();
            $clientId = $this->resolveClientId($validated, $user);
            $subtotal = 0;
            $items = [];

            foreach ($validated['items'] as $index => $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $unitPrice * $item['quantity'];
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total' => $lineTotal,
                    'product_image' => $this->resolveItemProductImage($request, $index, $item),
                    'remark' => filled($item['remark'] ?? null) ? trim((string) $item['remark']) : null,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $deliveryCost = $validated['delivery_cost'] ?? 0;
            $total = max(0, $subtotal + $deliveryCost - $discount);

            $order->update([
                'client_id' => $clientId,
                'commercial_id' => $validated['commercial_id'] ?? $order->commercial_id,
                'livreur_id' => $validated['livreur_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_cost' => $deliveryCost,
                'total' => $total,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'shipping_remark' => $validated['shipping_remark'] ?? $order->shipping_remark,
            ]);

            $keptImages = collect($items)->pluck('product_image')->filter()->all();
            foreach ($previousItems as $previousItem) {
                if ($previousItem->product_image && ! in_array($previousItem->product_image, $keptImages, true)) {
                    Storage::disk('public')->delete($previousItem->product_image);
                }
            }

            $order->items()->delete();

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_image' => $item['product_image'],
                    'remark' => $item['remark'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }

            if (! empty($validated['order_date'])) {
                $order->created_at = $validated['order_date'];
                $order->save();
            }

            $this->orderStock->syncOrderItems($order->fresh(['items.product']), $previousItems, $user);
        });

        $message = $user->isCommercial()
            ? 'Commande mise à jour. Les informations client ont été synchronisées avec la fiche clients.'
            : 'Commande mise à jour. Les informations client ont été synchronisées avec la fiche clients.';

        return redirect()
            ->route('orders.bon', $order)
            ->with('success', $message);
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        abort_unless($order->canBeModifiedBy(), 403, 'Cette commande ne peut pas être supprimée.');

        DB::transaction(function () use ($order) {
            if ($this->orderStock->holdsStock($order->status)) {
                $this->orderStock->restoreForOrder($order, auth()->user());
            }

            $order->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'Commande supprimée.');
    }

    public function show(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        return redirect()->route('orders.bon', $order);
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
            $order = $this->workflow->validateAndDispatchToPartner(
                $order,
                auth()->user(),
                $validated['delivery_partner_id'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        } catch (CathedisDispatchException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        $message = 'Commande transmise à Cathedis.';
        if ($order->partner_tracking_ref) {
            $message .= ' Réf livraison : '.$order->partner_tracking_ref.'.';
        }
        if ($order->cathedis_status_code) {
            $message .= ' Statut Cathedis : '.$order->cathedis_status_code;
            if ($order->status === \App\Enums\OrderStatus::Confirmee) {
                $message .= ' — commande confirmée.';
            }
        } else {
            $message .= ' Le statut sera synchronisé automatiquement.';
        }

        return back()->with('success', $message);
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

        $this->orderStock->restoreIfReleased($order, $previousStatus, auth()->user());

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

    public function bon(Order $order): View
    {
        $this->authorizeOrder($order);
        abort_unless($order->canViewBon(), 403);

        $order->load([
            'client.cityRecord',
            'commercial',
            'livreur',
            'deliveryPartner',
            'items.product',
            'statusHistories.user',
        ]);

        return view('orders.bon', [
            'order' => $order,
            'partners' => DeliveryPartner::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'statuses' => $this->workflow->allowedStatusesFor(auth()->user(), $order),
        ]);
    }

    public function deliveryNote(Order $order): View
    {
        $this->authorizeOrder($order);
        abort_unless($order->canViewBon(), 403);

        $order->load([
            'client.cityRecord',
            'commercial',
            'livreur',
            'deliveryPartner',
            'items.product',
        ]);

        return view('orders.delivery-note', compact('order'));
    }

    public function uploadItemProductImage(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->authorizeOrder($order);
        abort_unless($order->canUploadProductImage(), 403);
        abort_unless($item->order_id === $order->id, 404);
        abort_unless($item->product_id, 422, 'Ce produit ne peut pas recevoir d\'image.');

        ImageUpload::assertValidUpload($request, 'product_image');
        $request->validate(['product_image' => 'required|'.ImageUpload::RULE]);

        $path = ImageUpload::storeFromRequest($request, 'product_image', 'order-product-images');

        if ($item->product_image) {
            Storage::disk('public')->delete($item->product_image);
        }

        $item->update(['product_image' => $path]);

        $redirectRoute = $request->input('return_to') === 'print'
            ? route('orders.delivery-note', $order)
            : route('orders.bon', $order);

        return redirect($redirectRoute)
            ->with('success', 'Photo enregistrée pour « '.$item->product_name.' ».');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveItemProductImage(Request $request, int $index, array $item): ?string
    {
        if ($path = ImageUpload::storeFromRequest($request, "items.{$index}.product_image", 'order-product-images')) {
            return $path;
        }

        $existing = $item['existing_product_image'] ?? null;

        return filled($existing) ? $existing : null;
    }

    public function updateShippingRemark(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        abort_unless($order->canEditShippingRemark(), 403);

        $validated = $request->validate([
            'shipping_remark' => 'nullable|string|max:2000',
        ]);

        $order->update($validated);

        return back()->with('success', 'Remarque NB enregistrée.');
    }

    private function orderEditDeniedMessage(Order $order): string
    {
        $user = auth()->user();

        if ($user?->isCommercial() && $order->hasBeenValidatedByAdmin()) {
            return 'Cette commande a été validée par l\'administrateur et ne peut plus être modifiée.';
        }

        if ($user?->isCommercial() && $order->isAwaitingAdminValidation()) {
            return 'Cette commande est en attente de validation admin et ne peut plus être modifiée.';
        }

        return 'Cette commande ne peut plus être modifiée.';
    }

    private function authorizeOrder(Order $order): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isGestionnaireStock()) {
            return;
        }

        if ($user->isCommercial()) {
            if ($order->commercial_id !== $user->id && $order->created_by !== $user->id) {
                abort(403);
            }

            return;
        }

        if ($user->isLivreur() && $order->livreur_id !== $user->id) {
            abort(403);
        }
    }
}
