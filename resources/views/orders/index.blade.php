<x-admin-layout title="Commandes">
    @php
        $filterParams = request()->only(['client', 'category_id', 'ville', 'status']);
    @endphp
    <x-admin.list-page>
        <x-slot:toolbar>
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
                        <input
                            type="text"
                            id="filter-ville"
                            name="ville"
                            value="{{ request('ville') }}"
                            placeholder="Ville..."
                            class="form-input w-full text-sm py-1.5"
                        >
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
                    <div class="shrink-0 flex justify-end xl:justify-start xl:pl-6 xl:ml-2 xl:border-l xl:border-slate-200 dark:xl:border-slate-700">
                        <a href="{{ route('orders.create') }}" class="px-5 py-1.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 whitespace-nowrap shadow-sm">
                            + Nouvelle commande
                        </a>
                    </div>
                @endif
            </div>
        </x-slot:toolbar>

        <x-admin.data-table min-width="1680px" compact class="flex-1 min-h-0">
            @if($items->hasPages())
                <x-slot:footer>{{ $items->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-center">Date cmd</th>
                    <th class="text-left">Réf Bon</th>
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
                    <th class="text-center">Règl.</th>
                    <th class="text-left">Commercial</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @forelse($items as $item)
                    @php
                        $order = $item->order;
                        $client = $order->client;
                        $paymentClass = $order->paymentStatus() === 'paid'
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                    @endphp
                    <tr class="admin-row-hover">
                        <td class="admin-table-cell text-center text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="admin-table-cell">
                            <a href="{{ route('orders.show', $order) }}" class="link-brand font-medium">{{ $order->reference }}</a>
                        </td>
                        <td class="admin-table-cell-muted font-mono text-xs">{{ $client->formattedId() }}</td>
                        <td class="admin-table-cell font-medium">{{ $client->name }}</td>
                        <td class="admin-table-cell">{{ $client->city ?? '—' }}</td>
                        <td class="admin-table-cell-muted font-mono text-xs">{{ $item->product?->sku ?? '—' }}</td>
                        <td class="admin-table-cell">{{ $item->product_name }}</td>
                        <td class="admin-table-cell">{{ $item->product?->category?->name ?? '—' }}</td>
                        <td class="admin-table-cell text-center tabular-nums">{{ $item->quantity }}</td>
                        <td class="admin-table-cell text-right tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-center">
                            <x-admin.status-badge :status="$order->status" />
                        </td>
                        <td class="admin-table-cell text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $paymentClass }}">
                                {{ $order->paymentStatusLabel() }}
                            </span>
                        </td>
                        <td class="admin-table-cell">{{ $order->commercial?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="admin-table-cell text-center text-slate-500 dark:text-slate-400">Aucune ligne de commande</td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
