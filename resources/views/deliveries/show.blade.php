<x-admin-layout title="Livraison {{ $order->reference }}">
    <x-admin.list-page>
        <x-slot:toolbar>
            <a href="{{ route('deliveries.transport') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Retour transport</a>
        </x-slot:toolbar>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="admin-card p-5 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ $order->reference }}</h2>
                    <div class="flex flex-col items-end gap-2">
                        <x-admin.status-badge :status="$order->status" />
                        <x-admin.cathedis-status-badge :order="$order" label="Cathedis" class="items-end" />
                    </div>
                </div>
                <div><span class="text-slate-500">Client:</span> <strong>{{ $order->client->name }}</strong></div>
                <div><span class="text-slate-500">Téléphone:</span> {{ $order->client->phone ?? '—' }}</div>
                <div><span class="text-slate-500">Adresse:</span> {{ $order->client->address ?? '—' }}</div>
                <div><span class="text-slate-500">Ville:</span> {{ $order->client->city ?? '—' }}</div>
                <div><span class="text-slate-500">Partenaire:</span> {{ $order->deliveryPartner?->name ?? '—' }}</div>
                @if($order->deliveryReference())
                    <div><span class="text-slate-500">Réf livraison:</span> <span class="font-mono">{{ $order->deliveryReference() }}</span>@if($order->deliveryPartner) <span class="text-slate-500">({{ $order->deliveryPartner->name }})</span>@endif</div>
                @else
                    <div><span class="text-slate-500">Réf livraison:</span> —</div>
                @endif
                <div><span class="text-slate-500">Montant commande:</span> <strong>{{ number_format($order->total, 2, ',', ' ') }} DH</strong></div>
                <div><span class="text-slate-500">Solde à encaisser (COD):</span> <strong class="text-emerald-600">{{ number_format($order->balanceDue(), 2, ',', ' ') }} DH</strong></div>
            </div>

            @if($order->isDeliverableByPartner())
                <form method="POST" action="{{ route('deliveries.orders.complete', $order) }}" class="admin-card p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold">Confirmer la livraison</h3>
                    @error('outcome')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Résultat</label>
                        <select name="outcome" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                            <option value="delivered">Livré — paiement encaissé</option>
                            <option value="returned">Retour / non livré</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Montant encaissé (COD)</label>
                        <input type="number" step="0.01" min="0" name="amount_collected" value="{{ old('amount_collected', $order->balanceDue()) }}" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Notes</label>
                        <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600" placeholder="Observations livraison..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">Valider la livraison</button>
                </form>
            @else
                <div class="admin-card p-5 text-sm text-slate-500 dark:text-slate-400">
                    Cette commande n'est plus en cours de livraison.
                </div>
            @endif
        </div>
    </x-admin.list-page>
</x-admin-layout>
