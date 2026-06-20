<x-admin-layout title="BELDI-MALAKI" full-height>

@php
$user = auth()->user();
$cartIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
$docIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
$chargeIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>';
$profitIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
$walletIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
$usersIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
@endphp

<div class="flex flex-col flex-1 min-h-0 max-w-[1600px] w-full">
    {{-- KPIs fixes — seul le contenu en dessous scroll --}}
    <div class="shrink-0 pb-4 mb-4 border-b border-slate-200/80 dark:border-slate-800 bg-surface-muted dark:bg-slate-950">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            <x-admin.stat-card compact label="Ventes confirmées" :value="number_format($stats['revenue'], 2, ',', ' ').' DH'" color="brand" :icon="$cartIcon" />
            <x-admin.stat-card compact label="Total Commandes" :value="number_format($stats['total_orders'], 0, ',', ' ')" color="purple" :icon="$docIcon" />
            <x-admin.stat-card compact label="Total Charges" :value="number_format($stats['expenses'], 2, ',', ' ').' DH'" color="rose" :icon="$chargeIcon" />
            <x-admin.stat-card compact label="Bénéfice Net" :value="number_format($stats['net_profit'], 2, ',', ' ').' DH'" color="emerald" :icon="$profitIcon" />
            <x-admin.stat-card compact label="Paie Commerciaux" :value="number_format($stats['commercial_payroll_total'], 2, ',', ' ').' DH'" color="blue" :icon="$walletIcon" />
            <x-admin.stat-card compact label="Clients" :value="number_format($stats['clients_count'], 0, ',', ' ')" color="cyan" :icon="$usersIcon" />
        </div>

        @if(count($alerts))
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach(array_slice($alerts, 0, 3) as $alert)
                    <span class="{{ $alert['type'] === 'warning' ? 'admin-alert-warning' : 'admin-alert-info' }}">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $alert['message'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto space-y-5 overscroll-y-contain pr-1">
    {{-- Tableau opérationnel --}}
    <div class="admin-card p-5">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-4">
            <div>
                <h3 class="admin-section-title">Tableau Opérationnel Exercice-2026</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Du {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                </p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                @if($commercialMonth)
                    <input type="hidden" name="commercial_month" value="{{ $commercialMonth }}">
                @endif
                <div>
                    <label for="date_from" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Du</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="form-input text-sm py-1.5"
                    >
                </div>
                <div>
                    <label for="date_to" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Au</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="form-input text-sm py-1.5"
                    >
                </div>
                <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap">Rechercher</button>
            </form>
        </div>

        <x-admin.data-table compact min-width="1280px">
            @if($orderLines->hasPages())
                <x-slot:footer>{{ $orderLines->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-left">Date</th>
                    <th class="text-left">ID Client</th>
                    <th class="text-left">Nom Client</th>
                    <th class="text-left">Ville</th>
                    <th class="text-left">Catégorie</th>
                    <th class="text-left">Désignation</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-right">Prix U</th>
                    <th class="text-right">Montant Total</th>
                    <th class="text-center">Statut</th>
                    <th class="text-left">Commercial</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($orderLines as $item)
                    @php
                        $order = $item->order;
                        $client = $order->client;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="admin-table-cell text-left whitespace-nowrap text-slate-600 dark:text-slate-400">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="admin-table-cell-muted text-left font-mono text-xs">{{ $client->formattedId() }}</td>
                        <td class="admin-table-cell text-left font-medium">{{ $client->name }}</td>
                        <td class="admin-table-cell text-left">{{ $client->deliveryCityName() ?: '—' }}</td>
                        <td class="admin-table-cell text-left">{{ $item->product?->category?->name ?? '—' }}</td>
                        <td class="admin-table-cell text-left">{{ $item->product_name }}</td>
                        <td class="admin-table-cell text-center tabular-nums">{{ $item->quantity }}</td>
                        <td class="admin-table-cell text-right tabular-nums">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell text-center">
                            <x-admin.status-badge :status="$order->status" />
                        </td>
                        <td class="admin-table-cell text-left">{{ $order->commercial?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune commande sur cette période</td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </div>

    {{-- État commerciaux --}}
    <div class="admin-card p-5">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-4">
            <div>
                <h3 class="admin-section-title">État Commerciaux</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $commercialMonth)->locale('fr')->translatedFormat('F Y') }}
                    ({{ \Carbon\Carbon::parse($commercialDateFrom)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($commercialDateTo)->format('d/m/Y') }})
                </p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
                <div>
                    <label for="commercial_month" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Mois</label>
                    <input
                        type="month"
                        id="commercial_month"
                        name="commercial_month"
                        value="{{ $commercialMonth }}"
                        class="form-input text-sm py-1.5 w-full sm:w-[180px]"
                    >
                </div>
                <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap">Rechercher</button>
            </form>
        </div>

        <x-admin.data-table compact min-width="720px">
            <thead>
                <tr>
                    <th class="text-left">Date</th>
                    <th class="text-left">Nom Commercial</th>
                    <th class="text-center">Confir.</th>
                    <th class="text-center">Annulée</th>
                    <th class="text-center">Retour</th>
                    <th class="text-right">Chiffre ventes confirmées</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($commercialState as $row)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="admin-table-cell text-left whitespace-nowrap text-slate-600 dark:text-slate-400">{{ $row['date'] }}</td>
                        <td class="admin-table-cell text-left font-medium">{{ $row['commercial_name'] }}</td>
                        <td class="admin-table-cell text-center tabular-nums">{{ number_format($row['ventes_confir'], 0, ',', ' ') }}</td>
                        <td class="admin-table-cell text-center tabular-nums text-red-600 dark:text-red-400">{{ number_format($row['ventes_annulee'], 0, ',', ' ') }}</td>
                        <td class="admin-table-cell text-center tabular-nums text-orange-600 dark:text-orange-400">{{ number_format($row['ventes_retour'], 0, ',', ' ') }}</td>
                        <td class="admin-table-cell text-right tabular-nums font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ number_format($row['chiffre_confir'], 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune vente commercial sur cette période</td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </div>
    </div>
</div>
</x-admin-layout>
