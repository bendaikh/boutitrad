<x-admin-layout title="Commandes">
    @php
        $filterParams = request()->only(['client', 'category_id', 'ville', 'status']);
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

        <x-admin.list-page>
            <x-slot:toolbar>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isCommercial())
                    <div class="mb-3 xl:hidden">
                        <a
                            href="{{ route('orders.create') }}"
                            class="flex w-full items-center justify-center gap-2 px-4 py-2.5 bg-brand-600 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 shadow-sm"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Nouvelle commande
                        </a>
                    </div>
                @endif

                <div class="flex flex-col gap-3 w-full">
                    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4 w-full">
                        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[repeat(4,minmax(0,1fr))_auto] gap-2 flex-1 items-end min-w-0">
                            <div>
                                <label for="filter-client" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Client (ID ou nom)</label>
                                <input
                                    type="text"
                                    id="filter-client"
                                    name="client"
                                    value="{{ request('client') }}"
                                    placeholder="CL-00001 ou nom..."
                                    class="form-input w-full text-sm py-1.5"
                                >
                            </div>
                            <div>
                                <label for="filter-category" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Catégorie</label>
                                <select id="filter-category" name="category_id" class="form-input w-full text-sm py-1.5">
                                    <option value="">Toutes</option>
                                    <option value="none" @selected(request('category_id') === 'none')>Sans catégorie</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="filter-ville" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Ville livraison</label>
                                <select id="filter-ville" name="ville" class="form-input w-full text-sm py-1.5">
                                    <option value="">Toutes</option>
                                    @foreach($cities as $cityOption)
                                        <option value="{{ $cityOption->id }}" @selected((string) request('ville') === (string) $cityOption->id)>{{ $cityOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="filter-status" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Statut</label>
                                <select id="filter-status" name="status" class="form-input w-full text-sm py-1.5">
                                    <option value="">Tous</option>
                                    @foreach($statuses as $s)
                                        <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-nowrap items-end gap-1.5 shrink-0 sm:col-span-2 xl:col-auto">
                                <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap shrink-0">Filtrer</button>
                                <a
                                    href="{{ route('orders.index') }}"
                                    @class([
                                        'inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-lg border transition-colors',
                                        'border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20' => array_filter($filterParams),
                                        'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-300 dark:text-slate-600 pointer-events-none' => ! array_filter($filterParams),
                                    ])
                                    title="Annuler les filtres"
                                    aria-label="Annuler les filtres"
                                    @if(! array_filter($filterParams)) aria-disabled="true" @endif
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            </div>
                        </form>

                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isCommercial())
                            <div class="hidden xl:flex shrink-0 justify-end xl:pl-6 xl:ml-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-700">
                                <a href="{{ route('orders.create') }}" class="px-5 py-1.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 whitespace-nowrap shadow-sm">
                                    + Nouvelle commande
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Sélectionnez une commande · double-clic pour visualiser le bon</p>
                        <div class="flex flex-wrap items-center gap-2 notranslate" translate="no">
                            <x-admin.action-btn
                                icon="view"
                                label="Visualiser"
                                variant="info"
                                x-bind:disabled="!selectedOrderId || !selectedMeta?.canView"
                                @click="viewSelected()"
                            />
                            <x-admin.action-btn
                                icon="delete"
                                label="Supprimer"
                                variant="danger-solid"
                                x-bind:disabled="!selectedOrderId || !selectedMeta?.canModify"
                                @click="deleteSelected()"
                            />
                        </div>
                    </div>
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
