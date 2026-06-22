<x-admin-layout title="Bon {{ $order->reference }}">
    <div class="admin-bon-page">
        <div class="flex flex-wrap gap-3 items-center">
            @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && $order->status === \App\Enums\OrderStatus::Nouvelle)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">Étape de vérification — envoi admin après contrôle</span>
            @endif
            <a href="{{ route('orders.delivery-note', $order) }}" target="_blank" class="px-4 py-2 btn-dark">Aperçu imprimable</a>
            <a href="{{ route('orders.index') }}" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Retour liste</a>
        </div>

        @if($errors->has('workflow'))
            <div class="admin-flash-error">{{ $errors->first('workflow') }}</div>
        @endif
        @if($errors->has('product_image') || $errors->has('items.*.product_image'))
            <div class="admin-flash-error">{{ $errors->first('product_image') ?: $errors->first('items.*.product_image') }}</div>
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
                            <div class="flex flex-col items-end gap-2">
                                <x-admin.status-badge :status="$order->status" />
                                <x-admin.cathedis-status-badge :order="$order" label="Cathedis" class="items-end" />
                            </div>
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
                                        <th class="pb-2 pr-3 text-center align-middle">Photo</th>
                                        <th class="pb-2 pr-3 text-left align-middle">Produit</th>
                                        <th class="pb-2 pr-3 text-center align-middle w-16">Qté</th>
                                        <th class="pb-2 pr-3 text-right align-middle w-24">P.U.</th>
                                        <th class="pb-2 text-right align-middle w-28">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($order->items as $item)
                                        @php $itemPhotoUrl = $item->productImageUrl(); @endphp
                                        <tr>
                                            <td class="py-3 pr-3 align-middle text-center w-28">
                                                @if($itemPhotoUrl)
                                                    <img src="{{ $itemPhotoUrl }}" alt="{{ $item->product_name }}" class="w-20 h-20 rounded-lg border border-slate-200 dark:border-slate-600 object-cover">
                                                @else
                                                    <div class="w-20 h-20 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-[10px] text-slate-400 text-center px-1">Sans photo</div>
                                                @endif
                                                @if($order->canUploadProductImage() && $item->product_id)
                                                    <form method="POST" action="{{ route('orders.items.product-image', [$order, $item]) }}" enctype="multipart/form-data" class="mt-2 space-y-1">
                                                        @csrf
                                                        <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="block w-full text-[10px] text-slate-600 file:mr-1 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:bg-brand-50 file:text-brand-700">
                                                        <button type="submit" class="text-[10px] font-medium text-brand-600 hover:underline">{{ $itemPhotoUrl ? 'Remplacer' : 'Ajouter photo' }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-3 align-middle text-left">
                                                <div class="font-medium text-slate-900 dark:text-slate-100">{{ $item->product_name }}</div>
                                                @if($item->product?->sku)
                                                    <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $item->product->sku }}</div>
                                                @endif
                                                @if(filled($item->remark))
                                                    <div class="mt-2 rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-900/20 px-2 py-1.5">
                                                        <p class="text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300">NB</p>
                                                        <p class="text-xs whitespace-pre-wrap text-slate-700 dark:text-slate-200">{{ $item->remark }}</p>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-3 text-center align-middle tabular-nums">{{ $item->quantity }}</td>
                                            <td class="py-3 pr-3 text-right align-middle tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                                            <td class="py-3 text-right align-middle tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-slate-200 dark:border-slate-700">
                                    <tr><td colspan="4" class="pt-3 text-right text-slate-500">Sous-total articles</td><td class="pt-3 text-right tabular-nums">{{ number_format($order->itemsSubtotal(), 2, ',', ' ') }} DH</td></tr>
                                    @if($order->delivery_cost > 0)
                                        <tr><td colspan="4" class="pt-1 text-right text-slate-500">Livraison</td><td class="pt-1 text-right tabular-nums">{{ number_format($order->delivery_cost, 2, ',', ' ') }} DH</td></tr>
                                    @endif
                                    @if($order->discount > 0)
                                        <tr><td colspan="4" class="pt-1 text-right text-slate-500">Remise</td><td class="pt-1 text-right tabular-nums text-red-600">-{{ number_format($order->discount, 2, ',', ' ') }} DH</td></tr>
                                    @endif
                                    <tr><td colspan="4" class="pt-2 text-right font-semibold">Total</td><td class="pt-2 text-right font-bold text-brand-600 tabular-nums">{{ number_format($order->computedGrandTotal(), 2, ',', ' ') }} DH</td></tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-lg border border-dashed border-amber-300 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-900/10 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300 mb-2">NB — Remarques produits</p>
                            <p class="text-sm whitespace-pre-wrap text-slate-800 dark:text-slate-200">{{ $order->combinedShippingRemark() ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-bon-sidebar">
                @if(auth()->user()->isSuperAdmin() && $order->canBeValidatedByAdmin())
                    <div class="admin-card p-5 space-y-4 border-brand-200 dark:border-brand-800 bg-brand-50/40 dark:bg-brand-900/10">
                        <h3 class="font-semibold">Validation admin</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            @if($order->status === \App\Enums\OrderStatus::Confirmee)
                                La commande a été validée mais l'envoi Cathedis a échoué. Relancez la transmission.
                            @elseif($order->status === \App\Enums\OrderStatus::Nouvelle)
                                Commande en brouillon — validez directement ou attendez l'envoi du commercial.
                            @else
                                Vérifiez la photo et la NB saisies par le commercial, puis transmettez à Cathedis.
                            @endif
                        </p>

                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Photo produit</p>
                            @if($order->displayProductImageUrl())
                                <img src="{{ $order->displayProductImageUrl() }}" alt="" class="w-full max-w-[180px] rounded-lg border border-slate-200 object-cover">
                            @else
                                <p class="text-xs text-amber-600 dark:text-amber-400">Photo manquante — demandez au commercial de compléter.</p>
                            @endif
                        </div>

                        <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-900/10 p-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-amber-800 dark:text-amber-300 mb-1">NB — Remarques produits</p>
                            <p class="text-sm whitespace-pre-wrap text-slate-800 dark:text-slate-200">{{ $order->combinedShippingRemark() ?: '—' }}</p>
                        </div>

                        @if($order->client->deliveryCityName() === '' || empty($order->client->phone) || empty($order->client->address))
                            <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 text-xs text-amber-800 dark:text-amber-300 p-3">
                                Données client incomplètes (ville, téléphone ou adresse).
                            </div>
                        @endif

                        <form method="POST" action="{{ route('orders.dispatch', $order) }}" class="space-y-3">
                            @csrf
                            <select name="delivery_partner_id" class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600">
                                @foreach($partners as $partner)
                                    <option value="{{ $partner->id }}" @selected($partner->is_default)>{{ $partner->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700" @disabled(! $order->isReadyForAdminSubmission())>
                                {{ $order->status === \App\Enums\OrderStatus::Confirmee ? 'Relancer l\'envoi Cathedis' : 'Valider et transmettre à Cathedis' }}
                            </button>
                        </form>

                        @if($order->canBeRejectedByAdmin())
                        <form method="POST" action="{{ route('orders.reject', $order) }}" class="space-y-3 border-t border-slate-200 dark:border-slate-700 pt-3">
                            @csrf
                            <textarea name="notes" rows="2" placeholder="Motif de rejet..." class="w-full rounded-lg border-slate-300 text-sm dark:bg-slate-800 dark:border-slate-600"></textarea>
                            <button type="submit" class="w-full py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Rejeter</button>
                        </form>
                        @endif
                    </div>
                @elseif(auth()->user()->isSuperAdmin() && $order->hasBeenValidatedByAdmin())
                    <div class="admin-card p-5 space-y-3 border-emerald-200 dark:border-emerald-900 bg-emerald-50/50 dark:bg-emerald-900/10">
                        <h3 class="font-semibold text-emerald-800 dark:text-emerald-300">Commande validée</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Statut : <strong>{{ $order->status->label() }}</strong>
                            @if($order->deliveryReference())
                                · Réf livraison : <span class="font-mono">{{ $order->deliveryReference() }}</span>@if($order->deliveryPartner) ({{ $order->deliveryPartner->name }})@endif
                            @endif
                        </p>
                        <a href="{{ route('orders.delivery-note', $order) }}" target="_blank" class="block w-full py-2 text-center btn-dark text-sm">Visualiser / imprimer le bon</a>
                    </div>
                @endif

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

                @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && $order->status === \App\Enums\OrderStatus::Nouvelle)
                    <div class="admin-card p-5 space-y-4 border-sky-200 dark:border-sky-900 bg-sky-50/50 dark:bg-sky-900/10">
                        <div>
                            <h3 class="font-semibold text-sky-900 dark:text-sky-200">Vérification avant envoi</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Contrôlez le récapitulatif à gauche, complétez si besoin, puis envoyez à l'admin.</p>
                        </div>

                        <ul class="space-y-2 text-xs">
                            <li class="flex items-start gap-2">
                                <span @class(['mt-0.5 font-bold', $order->clientDetailsComplete() ? 'text-emerald-600' : 'text-amber-600'])>{{ $order->clientDetailsComplete() ? '✓' : '○' }}</span>
                                <span>Client : nom, téléphone, adresse, ville</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span @class(['mt-0.5 font-bold', $order->hasProductPhoto() ? 'text-emerald-600' : 'text-amber-600'])>{{ $order->hasProductPhoto() ? '✓' : '○' }}</span>
                                <span>Photo de chaque produit</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span @class(['mt-0.5 font-bold', $order->hasShippingRemark() ? 'text-emerald-600' : 'text-amber-600'])>{{ $order->hasShippingRemark() ? '✓' : '○' }}</span>
                                <span>Remarque NB</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="mt-0.5 font-bold text-emerald-600">✓</span>
                                <span>{{ $order->items->count() }} article(s) — Total {{ number_format($order->total, 2, ',', ' ') }} DH</span>
                            </li>
                        </ul>

                        @if(! $order->isReadyForAdminSubmission())
                            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 text-xs text-amber-800 dark:text-amber-300">
                                À compléter : {{ implode(', ', $order->missingItemsBeforeAdminSubmission()) }}.
                            </div>
                        @endif

                        @if($order->items->contains(fn ($item) => $item->productImageUrl()))
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                                <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wide">Photos enregistrées</p>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($order->items as $item)
                                        @if($itemPhotoUrl = $item->productImageUrl())
                                            <div>
                                                <img src="{{ $itemPhotoUrl }}" alt="{{ $item->product_name }}" class="w-full aspect-square rounded-lg border border-slate-200 dark:border-slate-600 object-cover">
                                                <p class="text-[10px] text-slate-500 mt-1 truncate">{{ $item->product_name }}</p>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($order->canBeEditedInForm())
                            <a href="{{ route('orders.edit', $order) }}" class="block w-full py-2 text-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700/50">Modifier la commande</a>
                        @endif

                        <form method="POST" action="{{ route('orders.submit', $order) }}" class="space-y-2" onsubmit="return confirm('Confirmer l\\'envoi de cette commande à l\\'administrateur ?');">
                            @csrf
                            <button type="submit" class="w-full py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed" @disabled(! $order->isReadyForAdminSubmission())>
                                Envoyer à l'admin
                            </button>
                        </form>
                    </div>
                @endif

                @if(auth()->user()->isCommercial() && $order->commercial_id === auth()->id() && ! $order->canBeEditedInForm())
                    <div class="admin-card p-5 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Commande verrouillée</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            @if($order->hasBeenValidatedByAdmin())
                                Cette commande a été validée par l'administrateur. Vous ne pouvez plus la modifier.
                            @elseif($order->isAwaitingAdminValidation())
                                Cette commande a été envoyée à l'administrateur. Vous ne pouvez plus la modifier tant qu'elle n'a pas été traitée.
                            @else
                                Cette commande ne peut plus être modifiée.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
