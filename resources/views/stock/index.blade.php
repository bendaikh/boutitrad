<x-admin-layout title="{{ auth()->user()->isCommercial() ? 'Consultation du stock' : 'Gestion du Stock' }}">
    @php
        $filterParams = request()->only(['category_id', 'status', 'etat']);
        $exportQuery = http_build_query(array_filter($filterParams, fn ($v) => $v !== null && $v !== ''));
        $exportSuffix = $exportQuery ? '?'.$exportQuery : '';
    @endphp
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-admin.stat-card
                    compact
                    label="Stock vendu"
                    :value="number_format($soldStockQty, 0, ',', ' ')"
                    color="purple"
                />
                <x-admin.stat-card
                    compact
                    label="Stock réel"
                    :value="number_format($realStockQty, 0, ',', ' ')"
                    color="brand"
                />
                <x-admin.stat-card
                    compact
                    label="Stock faible"
                    :value="number_format($lowStockQty, 0, ',', ' ')"
                    color="amber"
                />
                <x-admin.stat-card
                    compact
                    label="Stock en rupture"
                    :value="number_format($outOfStockQty, 0, ',', ' ')"
                    color="rose"
                />
            </div>
        </x-slot:toolbar>

        <x-admin.data-table min-width="1050px" compact class="flex-1 min-h-0">
            @if($products->hasPages())
                <x-slot:footer>{{ $products->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-left">Réf prod</th>
                    <th class="text-left">Désignation prod</th>
                    <th class="text-center">Catégorie prod</th>
                    <th class="text-center">Quantité prod</th>
                    <th class="text-center">Statut prod</th>
                    <th class="text-center">État prod</th>
                </tr>
                <tr class="admin-th-filter-row bg-slate-600 dark:bg-slate-800">
                    <th colspan="2"></th>
                    <th>
                        <form method="GET" class="admin-th-filter admin-th-filter--wide relative">
                            @foreach(collect($filterParams)->except('category_id') as $name => $value)
                                @if($value !== null && $value !== '')
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select name="category_id" onchange="this.form.submit()" aria-label="Filtrer par catégorie">
                                <option value="">Toutes</option>
                                <option value="none" @selected(request('category_id') === 'none')>Sans cat.</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <svg class="admin-th-filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </form>
                    </th>
                    <th></th>
                    <th class="text-center">
                        <form method="GET" class="admin-th-filter admin-th-filter--narrow relative">
                            @foreach(collect($filterParams)->except('status') as $name => $value)
                                @if($value !== null && $value !== '')
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select name="status" onchange="this.form.submit()" aria-label="Filtrer par statut">
                                <option value="">Tous</option>
                                <option value="dispo" @selected(request('status') === 'dispo')>Dispo</option>
                                <option value="faible" @selected(request('status') === 'faible')>Faible</option>
                                <option value="rupture" @selected(request('status') === 'rupture')>Rupture</option>
                            </select>
                            <svg class="admin-th-filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </form>
                    </th>
                    <th class="text-center">
                        <form method="GET" class="admin-th-filter admin-th-filter--narrow relative">
                            @foreach(collect($filterParams)->except('etat') as $name => $value)
                                @if($value !== null && $value !== '')
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select name="etat" onchange="this.form.submit()" aria-label="Filtrer par état">
                                <option value="">Tous</option>
                                <option value="actif" @selected(request('etat') === 'actif')>Actif</option>
                                <option value="inactif" @selected(request('etat') === 'inactif')>Inactif</option>
                            </select>
                            <svg class="admin-th-filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </form>
                    </th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @forelse($products as $product)
                    @php
                        $statusClass = match ($product->stockStatus()) {
                            'dispo' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                            'faible' => 'bg-yellow-300 text-yellow-900 dark:bg-yellow-900/50 dark:text-yellow-200 font-bold',
                            'rupture' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-semibold',
                        };
                    @endphp
                    <tr class="admin-row-hover">
                        <td class="admin-table-cell-muted font-mono text-xs">{{ $product->sku }}</td>
                        <td class="admin-table-cell font-medium">{{ $product->name }}</td>
                        <td class="admin-table-cell text-center">{{ $product->category?->name ?? '—' }}</td>
                        <td class="admin-table-cell text-center tabular-nums">{{ $product->quantity }}</td>
                        <td class="admin-table-cell text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $product->stockStatusLabel() }}
                            </span>
                        </td>
                        <td class="admin-table-cell text-center">
                            @if($product->is_active)
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Actif</span>
                            @else
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Inactif</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="admin-table-cell text-center text-slate-500 dark:text-slate-400">Aucun produit</td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>

        <div class="shrink-0 flex flex-wrap items-center justify-end gap-2 notranslate" translate="no">
            @if(auth()->user()->hasPermission('stock.print'))
            <x-admin.action-btn
                icon="print"
                label="Imprimer"
                @click="window.open('{{ route('stock.print') }}{{ $exportSuffix }}', '_blank')"
            />
            <x-admin.action-btn
                icon="save"
                label="Exporter PDF"
                :href="route('stock.export.pdf').$exportSuffix"
                target="_blank"
            />
            <x-admin.action-btn
                icon="save"
                label="Exporter Excel"
                variant="success"
                :href="route('stock.export.excel').$exportSuffix"
                target="_blank"
            />
            @endif
        </div>
    </x-admin.list-page>
</x-admin-layout>
