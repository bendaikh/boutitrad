<x-admin-layout title="Rapports" full-height>
    @php
        $money = fn (float $amount) => number_format($amount, 2, ',', ' ').' DH';
        $purchaseIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
        $salesIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        $chargeIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>';
        $profitIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
        $stockIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
    @endphp

    <div class="flex flex-col flex-1 min-h-0 w-full">
        <div class="shrink-0 pb-3 mb-3 border-b border-slate-200/80 dark:border-slate-800 bg-surface-muted dark:bg-slate-950">
            <div class="mb-3">
                <h2 class="admin-section-title">Rapport global</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Synthèse globale — toutes périodes</p>
            </div>

            <div class="report-kpi-strip">
                <x-admin.report-kpi label="Total Achats" :value="$money($summary['purchases_total'])" color="amber" :icon="$purchaseIcon" />
                <x-admin.report-kpi label="Total Ventes" :value="$money($summary['sales_total'])" color="brand" :icon="$salesIcon" />
                <x-admin.report-kpi label="Total Charges" :value="$money($summary['charges_total'])" color="rose" :icon="$chargeIcon" />
                <x-admin.report-kpi label="Bénéfice" :value="$money($summary['net_profit'])" color="emerald" :icon="$profitIcon" />
                <x-admin.report-kpi label="Valeur Stock" :value="$money($summary['stock_value'])" color="blue" :icon="$stockIcon" />
            </div>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto space-y-5 overscroll-y-contain pr-1">
            <div class="admin-card p-5">
                @include('reports.partials.table-toolbar', ['title' => 'Achats', 'section' => 'purchases'])
                <x-admin.data-table compact min-width="900px">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Réf produit</th>
                            <th class="text-left">Désignation</th>
                            <th class="text-left">Fournisseur</th>
                            <th class="text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($purchases as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="admin-table-cell whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="admin-table-cell font-mono text-xs">{{ $row['reference'] }}</td>
                                <td class="admin-table-cell font-medium">{{ $row['product'] }}</td>
                                <td class="admin-table-cell">{{ $row['supplier'] }}</td>
                                <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} DH</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun produit enregistré</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </div>

            <div id="ventes" class="admin-card p-5 scroll-mt-4">
                @include('reports.partials.table-toolbar', [
                    'title' => 'Ventes',
                    'section' => 'sales',
                    'showDateFilter' => true,
                    'dateFrom' => $salesFrom ?? null,
                    'dateTo' => $salesTo ?? null,
                ])
                <x-admin.data-table compact min-width="900px">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Réf Bn°</th>
                            <th class="text-left">Client</th>
                            <th class="text-left">Commercial</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right">Bénéfice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($sales as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="admin-table-cell whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="admin-table-cell font-mono text-xs">{{ $row['reference'] }}</td>
                                <td class="admin-table-cell font-medium">{{ $row['client'] }}</td>
                                <td class="admin-table-cell">{{ $row['commercial'] }}</td>
                                <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-right tabular-nums font-semibold {{ $row['profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($row['profit'], 2, ',', ' ') }} DH</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune vente confirmée</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($sales->isNotEmpty())
                        <tfoot>
                            <tr class="border-t-2 border-slate-200 dark:border-slate-700 font-semibold bg-slate-50 dark:bg-slate-800/40">
                                <td class="admin-table-cell text-right" colspan="4">Totaux</td>
                                <td class="admin-table-cell text-right tabular-nums">{{ number_format($salesAmountTotal ?? 0, 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-right tabular-nums {{ ($salesProfitTotal ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($salesProfitTotal ?? 0, 2, ',', ' ') }} DH</td>
                            </tr>
                        </tfoot>
                    @endif
                </x-admin.data-table>
            </div>

            <div class="admin-card p-5">
                @include('reports.partials.table-toolbar', ['title' => 'Mouvement Stock', 'section' => 'stock'])
                <x-admin.data-table compact min-width="900px">
                    <thead>
                        <tr>
                            <th class="text-left">Catégorie</th>
                            <th class="text-left">Produit</th>
                            <th class="text-center">Qté Entrée</th>
                            <th class="text-center">Qté Sortie</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($stockRows as $row)
                            @php
                                $statusClass = match ($row['status']) {
                                    'Dispo' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                                    'Faible' => 'bg-yellow-300 text-yellow-900 dark:bg-yellow-900/50 dark:text-yellow-200 font-bold',
                                    default => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-semibold',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="admin-table-cell">{{ $row['category'] }}</td>
                                <td class="admin-table-cell font-medium">{{ $row['product'] }}</td>
                                <td class="admin-table-cell text-center tabular-nums">{{ number_format($row['qty_in'], 0, ',', ' ') }}</td>
                                <td class="admin-table-cell text-center tabular-nums">{{ number_format($row['qty_out'], 0, ',', ' ') }}</td>
                                <td class="admin-table-cell text-center tabular-nums font-medium">{{ number_format($row['stock'], 0, ',', ' ') }}</td>
                                <td class="admin-table-cell text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun produit en stock</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </div>

            <div class="admin-card p-5">
                @include('reports.partials.table-toolbar', ['title' => 'Charges', 'section' => 'charges'])
                <x-admin.data-table compact min-width="760px">
                    <thead>
                        <tr>
                            <th class="text-left">Date</th>
                            <th class="text-left">Libellé</th>
                            <th class="text-right">Montant</th>
                            <th class="text-center">Type Règl.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($charges as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="admin-table-cell whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="admin-table-cell font-medium">{{ $row['label'] }}</td>
                                <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-center">{{ $row['payment_type'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune charge enregistrée</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-admin.data-table>
            </div>
        </div>
    </div>
</x-admin-layout>
