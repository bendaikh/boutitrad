<x-admin-layout title="Bon {{ $order->reference }}">
    <div class="admin-bon-page">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.delivery-note', $order) }}" target="_blank" class="px-4 py-2 btn-dark">Imprimer</a>
            <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Retour liste</a>
        </div>

        @if($errors->has('workflow'))
            <div class="admin-flash-error">{{ $errors->first('workflow') }}</div>
        @endif
        @if($errors->has('product_image'))
            <div class="admin-flash-error">{{ $errors->first('product_image') }}</div>
        @endif
        @if(session('success'))
            <div class="admin-flash-success">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">
            <div class="space-y-4 min-w-0">
                <div class="admin-card">
                    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-brand-50/80 to-white dark:from-brand-900/20 dark:to-slate-900">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">Bon de commande</p>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $order->reference }}</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <x-admin.status-badge :status="$order->status" />
                        </div>
                    </div>

                    @include('orders.partials.bon-info-grid', ['order' => $order])
                </div>

                <div class="admin-card">
                    <div class="p-5 space-y-5">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-700 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <th class="pb-2 pr-3 w-28">Image</th>
                                        <th class="pb-2 pr-3">Produit</th>
                                        <th class="pb-2 pr-3 text-center w-16">Qté</th>
                                        <th class="pb-2 pr-3 text-right w-24">P.U.</th>
                                        <th class="pb-2 text-right w-28">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($order->items as $item)
                                        @php $hasImage = (bool) $item->product?->imageUrl(); @endphp
                                        <tr>
                                            <td class="py-3 pr-3 align-top">
                                                <div class="flex flex-col items-start gap-2 min-w-[7rem]">
                                                    @if($hasImage)
                                                        <img src="{{ $item->product->imageUrl() }}" alt="" class="w-14 h-14 rounded-lg border border-slate-200 object-cover">
                                                    @else
                                                        <div class="w-14 h-14 rounded-lg border border-dashed border-slate-300 bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-[10px] text-slate-400 text-center leading-tight px-1">Sans image</div>
                                                    @endif
                                                    @if($order->canUploadProductImage() && $item->product_id)
                                                        <form method="POST" action="{{ route('orders.items.product-image', [$order, $item]) }}" enctype="multipart/form-data" class="w-full space-y-1.5">
                                                            @csrf
                                                            <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="block w-full text-[10px] text-slate-600 file:mr-1 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-900/40 dark:file:text-brand-300">
                                                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1 rounded-md border border-brand-200 dark:border-brand-700 bg-brand-50 dark:bg-brand-900/40 px-2 py-1.5 text-[10px] font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-900/60">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                                {{ $hasImage ? 'Changer image' : 'Ajouter image' }}
                                                            </button>
                                                        </form>
                                                    @elseif($order->canUploadProductImage() && ! $item->product_id)
                                                        <p class="text-[10px] text-amber-600 dark:text-amber-400 leading-tight">Produit non lié</p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 pr-3 align-top">
                                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $item->product_name }}</div>
                                                @if($item->product?->sku)
                                                    <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $item->product->sku }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-3 text-center align-top tabular-nums">{{ $item->quantity }}</td>
                                            <td class="py-3 pr-3 text-right align-top tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                                            <td class="py-3 text-right align-top tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-slate-200 dark:border-slate-700">
                                    <tr><td colspan="4" class="pt-3 text-right text-slate-500">Sous-total</td><td class="pt-3 text-right tabular-nums">{{ number_format($order->subtotal, 2, ',', ' ') }} DH</td></tr>
                                    @if($order->delivery_cost > 0)
                                        <tr><td colspan="4" class="pt-1 text-right text-slate-500">Livraison</td><td class="pt-1 text-right tabular-nums">{{ number_format($order->delivery_cost, 2, ',', ' ') }} DH</td></tr>
                                    @endif
                                    @if($order->discount > 0)
                                        <tr><td colspan="4" class="pt-1 text-right text-slate-500">Remise</td><td class="pt-1 text-right tabular-nums text-red-600">-{{ number_format($order->discount, 2, ',', ' ') }} DH</td></tr>
                                    @endif
                                    <tr><td colspan="4" class="pt-2 text-right font-semibold">Total</td><td class="pt-2 text-right font-bold text-brand-600 tabular-nums">{{ number_format($order->total, 2, ',', ' ') }} DH</td></tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-lg border border-dashed border-amber-300 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-900/10 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300 mb-2">NB — Remarque</p>
                            <p class="text-sm whitespace-pre-wrap text-slate-800 dark:text-slate-200">{{ $order->shipping_remark ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-bon-sidebar">
                @if($order->canManageBonContent())
                    <form method="POST" action="{{ route('orders.shipping-remark', $order) }}" class="admin-card p-5 space-y-3">
                        @csrf
                        @method('PATCH')
                        <h3 class="font-semibold">Saisir une NB</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Remarque visible sur le bon imprimé (livreur, fragilité, etc.).</p>
                        <textarea name="shipping_remark" rows="4" placeholder="Ex. Appeler avant livraison..." class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">{{ old('shipping_remark', $order->shipping_remark) }}</textarea>
                        @error('shipping_remark')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                        <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700">Enregistrer la NB</button>
                    </form>
                @endif

                @if(auth()->user()->isSuperAdmin() && in_array($order->status, [\App\Enums\OrderStatus::EnCours, \App\Enums\OrderStatus::Nouvelle]))
                    @if($order->client->deliveryCityName() === '' || empty($order->client->phone) || empty($order->client->address))
                        <div class="admin-card p-4 border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 text-xs text-amber-800 dark:text-amber-300">
                            Avant validation : vérifiez <strong>ville</strong>, <strong>téléphone</strong> et <strong>adresse</strong> client.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('orders.dispatch', $order) }}" class="admin-card p-5 space-y-3">
                        @csrf
                        <h3 class="font-semibold">Valider la commande</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Transmission automatique au partenaire de livraison.</p>
                        <select name="delivery_partner_id" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" @selected($partner->is_default)>{{ $partner->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700">Valider et transmettre</button>
                    </form>

                    <form method="POST" action="{{ route('orders.reject', $order) }}" class="admin-card p-5 space-y-3 border-red-200 dark:border-red-900">
                        @csrf
                        <h3 class="font-semibold text-red-700 dark:text-red-400">Rejeter</h3>
                        <textarea name="notes" rows="2" placeholder="Motif..." class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600"></textarea>
                        <button type="submit" class="w-full py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Rejeter la commande</button>
                    </form>
                @endif

                @if($order->hasBeenValidatedByAdmin())
                    <div class="admin-card p-5 text-sm space-y-2">
                        @if($order->validated_at)
                            <div><span class="text-slate-500">Validée le :</span> {{ $order->validated_at->format('d/m/Y H:i') }}</div>
                        @endif
                        @if($order->sent_to_partner_at)
                            <div><span class="text-slate-500">Envoyée partenaire :</span> {{ $order->sent_to_partner_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                @endif

                @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && $order->status === \App\Enums\OrderStatus::Nouvelle)
                    <form method="POST" action="{{ route('orders.submit', $order) }}" class="admin-card p-5 space-y-3">
                        @csrf
                        <h3 class="font-semibold">Envoyer à l'admin</h3>
                        <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700">Soumettre pour validation</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
