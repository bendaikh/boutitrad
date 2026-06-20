<x-admin-layout title="Balance">
    @php
        $filterParams = request()->only(['date_from', 'date_to', 'reference', 'client', 'ville']);
        $exportQuery = http_build_query(array_filter($filterParams, fn ($v) => $v !== null && $v !== ''));
        $exportSuffix = $exportQuery ? '?'.$exportQuery : '';
    @endphp

    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="space-y-3 w-full">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[repeat(5,minmax(0,1fr))_auto] gap-2 w-full items-end">
                    <div>
                        <label for="filter-date-from" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Date de</label>
                        <input
                            type="date"
                            id="filter-date-from"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="form-input w-full text-sm py-1.5"
                        >
                    </div>
                    <div>
                        <label for="filter-date-to" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Date à</label>
                        <input
                            type="date"
                            id="filter-date-to"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="form-input w-full text-sm py-1.5"
                        >
                    </div>
                    <div>
                        <label for="filter-reference" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">N° bon</label>
                        <input
                            type="text"
                            id="filter-reference"
                            name="reference"
                            value="{{ request('reference') }}"
                            placeholder="BN-20260001"
                            class="form-input w-full text-sm py-1.5 font-mono"
                        >
                    </div>
                    <div>
                        <label for="filter-client" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">ID ou nom client</label>
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
                        <label for="filter-ville" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Ville</label>
                        <input
                            type="text"
                            id="filter-ville"
                            name="ville"
                            value="{{ request('ville') }}"
                            placeholder="Ville..."
                            class="form-input w-full text-sm py-1.5"
                        >
                    </div>
                    <div class="flex flex-nowrap items-end gap-1.5 shrink-0 sm:col-span-2 xl:col-auto">
                        <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap shrink-0">Filtrer</button>
                        <a
                            href="{{ route('sales.balance') }}"
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

                <div class="flex flex-wrap items-center justify-end gap-2 notranslate" translate="no">
                    <x-admin.action-btn
                        icon="print"
                        label="Imprimer"
                        @click="window.open('{{ route('sales.balance.print') }}{{ $exportSuffix }}', '_blank')"
                    />
                    <x-admin.action-btn
                        icon="save"
                        label="Exporter PDF"
                        :href="route('sales.balance.export.pdf').$exportSuffix"
                        target="_blank"
                    />
                    <x-admin.action-btn
                        icon="save"
                        label="Exporter Excel"
                        variant="success"
                        :href="route('sales.balance.export.excel').$exportSuffix"
                        target="_blank"
                    />
                </div>
            </div>
        </x-slot:toolbar>

        <div class="shrink-0 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-admin.stat-card
                compact
                variant="solid"
                label="Nombre de commandes"
                :value="number_format($stats['orders_count'], 0, ',', ' ')"
                color="green"
            />
            <x-admin.stat-card
                compact
                variant="solid"
                label="Montant total des commandes"
                :value="number_format($stats['orders_total'], 2, ',', ' ').' DH'"
                color="blue"
            />
            <x-admin.stat-card
                compact
                variant="solid"
                label="Solde des commandes"
                :value="number_format($stats['orders_balance'], 2, ',', ' ').' DH'"
                color="red"
            />
        </div>

        <x-admin.data-table min-width="1760px" compact class="flex-1 min-h-0">
            @if($items->hasPages())
                <x-slot:footer>{{ $items->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-center">Date</th>
                    <th class="text-left">N° bon</th>
                    <th class="text-left">ID client</th>
                    <th class="text-left">Nom client</th>
                    <th class="text-left">Ville livraison</th>
                    <th class="text-left">Désignation cmd</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-right">Prix U</th>
                    <th class="text-right">Mnt total</th>
                    <th class="text-right">Mnt payé</th>
                    <th class="text-left">Mode paiement</th>
                    <th class="text-right">Solde</th>
                    <th class="text-left">Commercial</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @forelse($items as $item)
                    @php
                        $order = $item->order;
                        $client = $order->client;
                    @endphp
                    <tr class="admin-row-hover">
                        <td class="admin-table-cell text-center text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="admin-table-cell">
                            <a href="{{ route('orders.show', $order) }}" class="link-brand font-medium font-mono text-xs">{{ $order->reference }}</a>
                        </td>
                        <td class="admin-table-cell-muted font-mono text-xs">{{ $client->formattedId() }}</td>
                        <td class="admin-table-cell font-medium">{{ $client->name }}</td>
                        <td class="admin-table-cell">{{ $client->city ?? '—' }}</td>
                        <td class="admin-table-cell">{{ $item->product_name }}</td>
                        <td class="admin-table-cell text-center tabular-nums">{{ $item->quantity }}</td>
                        <td class="admin-table-cell text-right tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-right tabular-nums">{{ number_format($order->paidAmount(), 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell">{{ $order->payment_mode?->label() ?? '—' }}</td>
                        <td class="admin-table-cell text-right tabular-nums {{ $order->balanceDue() > 0 ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                            {{ number_format($order->balanceDue(), 2, ',', ' ') }} DH
                        </td>
                        <td class="admin-table-cell">{{ $order->commercial?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="admin-table-cell text-center text-slate-500 dark:text-slate-400 py-8">
                            Aucune ligne trouvée
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
