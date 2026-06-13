<x-admin-layout title="Commande {{ $order->reference }}">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="px-4 py-2 btn-dark">Facture / Imprimer</a>
                <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200">Retour</a>
            </div>
        </x-slot:toolbar>

        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex flex-col gap-6 min-h-0 overflow-y-auto">
                <x-admin.data-table class="flex-1 min-h-0">
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <span>{{ $order->reference }}</span>
                            <x-admin.status-badge :status="$order->status" />
                        </div>
                    </x-slot:header>
                    <thead><tr>
                        <th class="text-left">Produit</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">P.U.</th>
                        <th class="text-right">Total</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @foreach($order->items as $item)
                            <tr>
                                <td class="px-4 py-2">{{ $item->product_name }}</td>
                                <td class="px-4 py-2 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($item->total, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t bg-white">
                        <tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Sous-total</td><td class="px-4 py-2 text-right">{{ number_format($order->subtotal, 2, ',', ' ') }} DH</td></tr>
                        @if($order->delivery_cost > 0)<tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Coût livraison</td><td class="px-4 py-2 text-right">{{ number_format($order->delivery_cost, 2, ',', ' ') }} DH</td></tr>@endif
                        @if($order->discount > 0)<tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Remise</td><td class="px-4 py-2 text-right text-red-600">-{{ number_format($order->discount, 2, ',', ' ') }} DH</td></tr>@endif
                        <tr><td colspan="3" class="px-4 py-2 text-right font-semibold">Total</td><td class="px-4 py-2 text-right font-bold text-brand-600">{{ number_format($order->total, 2, ',', ' ') }} DH</td></tr>
                    </tfoot>
                </x-admin.data-table>

                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm shrink-0">
                    <h3 class="font-semibold mb-3">Historique</h3>
                    <div class="space-y-2">
                        @foreach($order->statusHistories as $history)
                            <div class="flex items-center gap-3 text-sm border-l-2 border-brand-200 pl-3">
                                <span class="text-slate-500 dark:text-slate-400">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</span>
                                @if($history->notes)<span class="text-slate-500 dark:text-slate-400">- {{ $history->notes }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-4 shrink-0 overflow-y-auto">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-sm space-y-2">
                    <div><span class="text-slate-500 dark:text-slate-400">Client:</span> <strong>{{ $order->client->name }}</strong></div>
                    <div><span class="text-slate-500 dark:text-slate-400">Commercial:</span> {{ $order->commercial?->name ?? '-' }}</div>
                    <div><span class="text-slate-500 dark:text-slate-400">Livreur:</span> {{ $order->livreur?->name ?? '-' }}</div>
                    <div><span class="text-slate-500 dark:text-slate-400">Créée le:</span> {{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->isCommercial())
                <form method="POST" action="{{ route('orders.status', $order) }}" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                    @csrf @method('PATCH')
                    <h3 class="font-semibold">Mettre à jour le statut</h3>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach($statuses as $s)<option value="{{ $s->value }}" @selected($order->status === $s)>{{ $s->label() }}</option>@endforeach
                    </select>
                    @if(auth()->user()->isSuperAdmin())
                    <select name="commercial_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Commercial...</option>
                        @foreach($commercials as $c)<option value="{{ $c->id }}" @selected($order->commercial_id == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    <select name="livreur_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Livreur...</option>
                        @foreach($livreurs as $l)<option value="{{ $l->id }}" @selected($order->livreur_id == $l->id)>{{ $l->name }}</option>@endforeach
                    </select>
                    @endif
                    <textarea name="notes" rows="2" placeholder="Notes..." class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700">Mettre à jour</button>
                </form>
                @endif
            </div>
        </div>
    </x-admin.list-page>
</x-admin-layout>
