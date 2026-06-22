<x-admin-layout title="Commandes">
    @php
        $filterParams = request()->only(['delivery_ref', 'date_from', 'date_to', 'category_id', 'ville', 'cathedis_status']);
        $ordersMeta = [];

        foreach ($items as $item) {
            $order = $item->order;

            if (isset($ordersMeta[$order->id])) {
                continue;
            }

            $ordersMeta[$order->id] = [
                'canModify' => $order->canBeModifiedBy(auth()->user()),
                'canView' => $order->canViewBon(auth()->user()),
                'bonUrl' => route('orders.bon', $order),
                'showUrl' => route('orders.bon', $order),
                'destroyUrl' => route('orders.destroy', $order),
                'reference' => $order->reference,
            ];
        }
    @endphp

    <div
        x-data="{
            selectedOrderId: null,
            ordersMeta: @js($ordersMeta),
            get selectedMeta() {
                if (! this.selectedOrderId) {
                    return null;
                }

                return this.ordersMeta[this.selectedOrderId] ?? this.ordersMeta[String(this.selectedOrderId)] ?? null;
            },
            viewSelected() {
                const meta = this.selectedMeta;

                if (! meta?.canView) {
                    return;
                }

                window.location.href = meta.bonUrl;
            },
            deleteSelected() {
                const meta = this.selectedMeta;

                if (! meta?.canModify || ! confirm('Supprimer la commande ' + meta.reference + ' ?')) {
                    return;
                }

                const form = document.getElementById('order-delete-form');
                form.action = meta.destroyUrl;
                form.submit();
            },
        }"
    >
        <form id="order-delete-form" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <x-admin.list-page class="!gap-2 !py-3 sm:!py-4">
            <x-slot:toolbar>
                <div class="admin-form-shell max-w-full shadow-sm">
                    <form method="GET">
                        <div class="px-2 py-1.5 sm:px-3 sm:py-2 border-b border-slate-200 dark:border-slate-700">
                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-[repeat(6,minmax(0,1fr))_auto] gap-1.5 sm:gap-2 items-end">
                                <div>
                                    <label for="filter-date-from" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Du</label>
                                    <input type="date" id="filter-date-from" name="date_from" value="{{ request('date_from') }}" class="form-input w-full text-xs py-1">
                                </div>
                                <div>
                                    <label for="filter-date-to" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Au</label>
                                    <input type="date" id="filter-date-to" name="date_to" value="{{ request('date_to') }}" class="form-input w-full text-xs py-1">
                                </div>
                                <div class="col-span-2 sm:col-span-1 xl:col-span-1">
                                    <label for="filter-delivery-ref" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5 truncate" title="Réf Bon Livraison Cathedis">Réf Cathedis</label>
                                    <input type="text" id="filter-delivery-ref" name="delivery_ref" value="{{ request('delivery_ref') }}" placeholder="Réf…" class="form-input w-full text-xs py-1 font-mono">
                                </div>
                                <div>
                                    <label for="filter-category" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Catégorie</label>
                                    <select id="filter-category" name="category_id" class="form-input w-full text-xs py-1">
                                        <option value="">Toutes</option>
                                        <option value="none" @selected(request('category_id') === 'none')>Sans cat.</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="filter-ville" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Ville</label>
                                    <select id="filter-ville" name="ville" class="form-input w-full text-xs py-1">
                                        <option value="">Toutes</option>
                                        @foreach($cities as $cityOption)
                                            <option value="{{ $cityOption->id }}" @selected((string) request('ville') === (string) $cityOption->id)>{{ $cityOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-2 sm:col-span-1 xl:col-span-1">
                                    <label for="filter-cathedis-status" class="block text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Statut Cathedis</label>
                                    <select id="filter-cathedis-status" name="cathedis_status" class="form-input w-full text-xs py-1">
                                        <option value="">Tous</option>
                                        @foreach($statuses as $statusOption)
                                            <option value="{{ $statusOption }}" @selected(request('cathedis_status') === $statusOption)>
                                                {{ \App\Support\CathedisStatusMapper::filterLabel($statusOption) }}
                                            </option>
                                        @endforeach
                                        <option value="__non_sync__" @selected(request('cathedis_status') === '__non_sync__')>Non synchronisé</option>
                                    </select>
                                </div>
                                <div class="col-span-2 sm:col-span-3 xl:col-auto flex items-end gap-1 shrink-0">
                                    <button type="submit" class="px-3 py-1 btn-dark text-xs whitespace-nowrap">Filtrer</button>
                                    <a
                                        href="{{ route('orders.index') }}"
                                        @class([
                                            'inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-md border transition-colors',
                                            'border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20' => array_filter($filterParams),
                                            'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-300 dark:text-slate-600 pointer-events-none' => ! array_filter($filterParams),
                                        ])
                                        title="Réinitialiser les filtres"
                                        aria-label="Réinitialiser les filtres"
                                        @if(! array_filter($filterParams)) aria-disabled="true" @endif
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="px-2 py-1 sm:px-3 sm:py-1.5 bg-slate-50/80 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-1.5">
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 hidden sm:block">Double-clic sur une ligne pour ouvrir le bon</p>
                            <div class="flex flex-wrap items-center gap-1.5 ml-auto notranslate" translate="no">
                                <x-admin.action-btn
                                    icon="view"
                                    label="Visualiser"
                                    variant="info"
                                    class="!px-2.5 !py-1 !text-xs"
                                    x-bind:disabled="!selectedOrderId || !selectedMeta?.canView"
                                    @click="viewSelected()"
                                />
                                <x-admin.action-btn
                                    icon="delete"
                                    label="Supprimer"
                                    variant="danger-solid"
                                    class="!px-2.5 !py-1 !text-xs"
                                    x-bind:disabled="!selectedOrderId || !selectedMeta?.canModify"
                                    @click="deleteSelected()"
                                />
                                @if(auth()->user()->isSuperAdmin() || auth()->user()->isCommercial())
                                    <a
                                        href="{{ route('orders.create') }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-brand-600 text-white rounded-lg text-xs font-medium hover:bg-brand-700 whitespace-nowrap"
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Nouvelle
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </x-slot:toolbar>

            <x-admin.data-table min-width="1780px" compact class="flex-1 min-h-0">
                @if($items->hasPages())
                    <x-slot:footer>{{ $items->links() }}</x-slot:footer>
                @endif
                <thead>
                    <tr>
                        <th class="text-center">Date cmd</th>
                        <th class="text-left">Réf Bon</th>
                        <th class="text-left">Réf livraison</th>
                        <th class="text-left">ID client</th>
                        <th class="text-left">Nom client</th>
                        <th class="text-left">Ville livraison</th>
                        <th class="text-left">Réf prod</th>
                        <th class="text-left">Désignation prod</th>
                        <th class="text-left">Catégorie</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-right">Prix u</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Statut Cathedis</th>
                        <th class="text-center">Règl.</th>
                        <th class="text-left">Commercial</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse($items as $item)
                        @php
                            $order = $item->order;
                            $client = $order->client;
                            $canModify = $order->canBeModifiedBy(auth()->user());
                            $canView = $order->canViewBon(auth()->user());
                            $paymentClass = $order->paymentStatus() === 'paid'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                        @endphp
                        <tr
                            class="admin-row-hover cursor-pointer"
                            :class="selectedOrderId === {{ $order->id }} ? 'admin-row-selected' : ''"
                            @click="selectedOrderId = {{ $order->id }}"
                            @dblclick="if ({{ $canView ? 'true' : 'false' }}) window.location.href = '{{ route('orders.bon', $order) }}'"
                        >
                            <td class="admin-table-cell text-center text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="admin-table-cell text-left">
                                <a href="{{ route('orders.bon', $order) }}" class="link-brand font-medium font-mono text-xs" @click.stop>{{ $order->reference }}</a>
                            </td>
                            <td class="admin-table-cell-muted text-left font-mono text-xs">{{ $order->deliveryReference() ?? '—' }}</td>
                            <td class="admin-table-cell-muted text-left font-mono text-xs">{{ $client->formattedId() }}</td>
                            <td class="admin-table-cell text-left font-medium">{{ $client->name }}</td>
                            <td class="admin-table-cell text-left">{{ $client->city ?? '—' }}</td>
                            <td class="admin-table-cell-muted text-left font-mono text-xs">{{ $item->product?->sku ?? '—' }}</td>
                            <td class="admin-table-cell text-left">{{ $item->product_name }}</td>
                            <td class="admin-table-cell text-left">{{ $item->product?->category?->name ?? '—' }}</td>
                            <td class="admin-table-cell text-center tabular-nums">{{ $item->quantity }}</td>
                            <td class="admin-table-cell text-right tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell text-center">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="admin-table-cell text-center">
                                <x-admin.cathedis-status-badge :order="$order" compact />
                            </td>
                            <td class="admin-table-cell text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $paymentClass }}">
                                    {{ $order->paymentStatusLabel() }}
                                </span>
                            </td>
                            <td class="admin-table-cell text-left">{{ $order->commercial?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="admin-table-cell text-center text-slate-500 dark:text-slate-400">Aucune ligne de commande</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
