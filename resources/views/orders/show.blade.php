<x-admin-layout title="Commande {{ $order->reference }}">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="px-4 py-2 btn-dark">Facture / Imprimer</a>
                <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Retour</a>
            </div>
        </x-slot:toolbar>

        @if($errors->has('workflow'))
            <div class="mb-4 admin-flash-error">{{ $errors->first('workflow') }}</div>
        @endif

        <div class="mb-4 admin-card p-4">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">Circuit de la commande</h3>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span @class(['px-2.5 py-1 rounded-full font-medium', $order->status === \App\Enums\OrderStatus::Nouvelle ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'])>1. Saisie commercial</span>
                <span class="text-slate-400">→</span>
                <span @class(['px-2.5 py-1 rounded-full font-medium', $order->isAwaitingAdminValidation() ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'])>2. Validation admin</span>
                <span class="text-slate-400">→</span>
                <span @class(['px-2.5 py-1 rounded-full font-medium', $order->isWithPartner() || $order->status === \App\Enums\OrderStatus::Expediee ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'])>3. Partenaire livraison</span>
                <span class="text-slate-400">→</span>
                <span @class(['px-2.5 py-1 rounded-full font-medium', $order->status === \App\Enums\OrderStatus::Livree ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'])>4. Livré + paiement</span>
            </div>
        </div>

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
                    <tfoot class="border-t bg-white dark:bg-slate-900">
                        <tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Sous-total</td><td class="px-4 py-2 text-right">{{ number_format($order->subtotal, 2, ',', ' ') }} DH</td></tr>
                        @if($order->delivery_cost > 0)<tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Coût livraison</td><td class="px-4 py-2 text-right">{{ number_format($order->delivery_cost, 2, ',', ' ') }} DH</td></tr>@endif
                        @if($order->discount > 0)<tr><td colspan="3" class="px-4 py-2 text-right text-slate-500 dark:text-slate-400">Remise</td><td class="px-4 py-2 text-right text-red-600">-{{ number_format($order->discount, 2, ',', ' ') }} DH</td></tr>@endif
                        <tr><td colspan="3" class="px-4 py-2 text-right font-semibold">Total</td><td class="px-4 py-2 text-right font-bold text-brand-600">{{ number_format($order->total, 2, ',', ' ') }} DH</td></tr>
                    </tfoot>
                </x-admin.data-table>

                <div class="admin-card p-5 shrink-0">
                    <h3 class="font-semibold mb-3">Historique</h3>
                    <div class="space-y-2">
                        @foreach($order->statusHistories as $history)
                            <div class="flex items-center gap-3 text-sm border-l-2 border-brand-200 pl-3">
                                <span class="text-slate-500 dark:text-slate-400">{{ $history->created_at->format('d/m/Y H:i') }}</span>
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</span>
                                @if($history->notes)<span class="text-slate-500 dark:text-slate-400">— {{ $history->notes }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-4 shrink-0 overflow-y-auto">
                <div class="admin-card p-5 text-sm space-y-2">
                    <div><span class="text-slate-500 dark:text-slate-400">Client:</span> <strong>{{ $order->client->name }}</strong></div>
                    <div><span class="text-slate-500 dark:text-slate-400">Ville:</span> {{ $order->client->city ?? '—' }}</div>
                    <div><span class="text-slate-500 dark:text-slate-400">Commercial:</span> {{ $order->commercial?->name ?? '—' }}</div>
                    <div><span class="text-slate-500 dark:text-slate-400">Partenaire:</span> {{ $order->deliveryPartner?->name ?? '—' }}</div>
                    @if($order->partner_tracking_ref)
                        <div><span class="text-slate-500 dark:text-slate-400">Ref. partenaire:</span> <span class="font-mono text-xs">{{ $order->partner_tracking_ref }}</span></div>
                    @endif
                    <div><span class="text-slate-500 dark:text-slate-400">Créée le:</span> {{ $order->created_at->format('d/m/Y H:i') }}</div>
                    @if($order->submitted_to_admin_at)
                        <div><span class="text-slate-500 dark:text-slate-400">Envoyée admin:</span> {{ $order->submitted_to_admin_at->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($order->sent_to_partner_at)
                        <div><span class="text-slate-500 dark:text-slate-400">Envoyée partenaire:</span> {{ $order->sent_to_partner_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>

                @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && $order->isAwaitingAdminValidation())
                    <div class="admin-card p-5 border-yellow-200 dark:border-yellow-900 bg-yellow-50 dark:bg-yellow-900/20">
                        <h3 class="font-semibold text-yellow-800 dark:text-yellow-300">En attente de validation admin</h3>
                        <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">Votre commande a été transmise à l'administrateur. Vous serez notifié après validation et envoi au partenaire de livraison.</p>
                    </div>
                @endif

                @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && $order->status === \App\Enums\OrderStatus::Nouvelle)
                    <form method="POST" action="{{ route('orders.submit', $order) }}" class="admin-card p-5 space-y-3">
                        @csrf
                        <h3 class="font-semibold">Étape 1 — Envoyer à l'admin</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">La commande sera transmise à l'administrateur pour validation avant livraison.</p>
                        <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Envoyer à l'admin</button>
                    </form>
                @endif

                @if(auth()->user()->isSuperAdmin() && in_array($order->status, [\App\Enums\OrderStatus::EnCours, \App\Enums\OrderStatus::Nouvelle]))
                    <form method="POST" action="{{ route('orders.dispatch', $order) }}" class="admin-card p-5 space-y-3">
                        @csrf
                        <h3 class="font-semibold">Étape 2 — Valider et envoyer au partenaire</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Validation admin puis transmission automatique au partenaire de livraison (Cathedis).</p>
                        <select name="delivery_partner_id" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" @selected($partner->is_default)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700">Valider et transmettre</button>
                    </form>
                    <form method="POST" action="{{ route('orders.reject', $order) }}" class="admin-card p-5 space-y-3 border-red-200 dark:border-red-900">
                        @csrf
                        <h3 class="font-semibold text-red-700 dark:text-red-400">Rejeter la commande</h3>
                        <textarea name="notes" rows="2" placeholder="Motif..." class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600"></textarea>
                        <button type="submit" class="w-full py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Rejeter</button>
                    </form>
                @endif

                @if($order->isDeliverableByPartner() && (auth()->user()->isSuperAdmin() || auth()->user()->isLivreur()))
                    <div class="admin-card p-5 space-y-3">
                        <h3 class="font-semibold">Étape 3 — Livraison partenaire</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Confirmer la livraison et le paiement COD.</p>
                        <a href="{{ route('deliveries.orders.show', $order) }}" class="block w-full py-2 text-center bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Ouvrir fiche livraison</a>
                    </div>
                @endif

                @if(auth()->user()->isSuperAdmin() && count($statuses))
                <form method="POST" action="{{ route('orders.status', $order) }}" class="admin-card p-5 space-y-3">
                    @csrf @method('PATCH')
                    <h3 class="font-semibold text-xs text-slate-500">Ajustement manuel (admin)</h3>
                    <select name="status" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                        @foreach($statuses as $s)<option value="{{ $s->value }}" @selected($order->status === $s)>{{ $s->label() }}</option>@endforeach
                    </select>
                    <textarea name="notes" rows="2" placeholder="Notes..." class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600"></textarea>
                    <button type="submit" class="w-full py-2 bg-slate-600 text-white rounded-lg text-sm hover:bg-slate-700">Mettre à jour</button>
                </form>
                @endif
            </div>
        </div>
    </x-admin.list-page>
</x-admin-layout>
