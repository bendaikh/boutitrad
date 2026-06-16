<x-admin-layout title="BELDI-MALAKI">

@php
$user = auth()->user();
$cartIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
$docIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
$chargeIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>';
$profitIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
$walletIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
$usersIcon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
@endphp

<div class="space-y-5 max-w-[1600px]">
    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        <x-admin.stat-card compact label="Ventes" :value="number_format($stats['revenue'], 2, ',', ' ').' DH'" color="brand" :icon="$cartIcon" />
        <x-admin.stat-card compact label="Total Commandes" :value="number_format($stats['total_orders'], 0, ',', ' ')" color="purple" :icon="$docIcon" />
        <x-admin.stat-card compact label="Total Charges" :value="number_format($stats['expenses'], 2, ',', ' ').' DH'" color="rose" :icon="$chargeIcon" />
        <x-admin.stat-card compact label="Bénéfice Net" :value="number_format($stats['net_profit'], 2, ',', ' ').' DH'" color="emerald" :icon="$profitIcon" />
        <x-admin.stat-card compact label="Trésorerie" :value="number_format($stats['treasury'], 2, ',', ' ').' DH'" color="blue" :icon="$walletIcon" />
        <x-admin.stat-card compact label="Clients" :value="number_format($stats['clients_count'], 0, ',', ' ')" color="cyan" :icon="$usersIcon" />
    </div>

    {{-- Alerts --}}
    @if(count($alerts))
        <div class="flex flex-wrap gap-2">
            @foreach(array_slice($alerts, 0, 3) as $alert)
                <span class="{{ $alert['type'] === 'warning' ? 'admin-alert-warning' : 'admin-alert-info' }}">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $alert['message'] }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Main chart --}}
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="admin-section-title">Distribution des Commandes <span class="admin-section-subtitle">({{ now()->year }})</span></h3>
            <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            </div>
        </div>
        <div class="h-80 sm:h-96"><canvas id="orderDistributionChart"></canvas></div>
    </div>

    {{-- Monthly reports --}}
    <div class="admin-card p-4">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5 pb-4 border-b border-slate-100 dark:border-slate-700">
            <div>
                <h3 class="admin-section-title">Rapports mensuels</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $monthLabel }}</p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="report-month" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Mois</label>
                    <input
                        type="month"
                        id="report-month"
                        name="month"
                        value="{{ $selectedMonth }}"
                        class="form-input text-sm py-1.5"
                    >
                </div>
                <button type="submit" class="px-4 py-1.5 btn-dark text-sm">Afficher</button>
            </form>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
            <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col min-h-[320px]">
                <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-1">Diagramme commerciaux</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Chiffre réalisé par commercial</p>
                <div class="flex-1 min-h-[260px]">
                    <canvas id="commercialChart"></canvas>
                </div>
            </div>

            <x-admin.data-table compact class="min-h-[320px]">
                <x-slot:header>Ventes par commercial</x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">ID</th>
                        <th class="text-left">Nom commercial</th>
                        <th class="text-right">Confi.</th>
                        <th class="text-right">Annu.</th>
                        <th class="text-right">Retour</th>
                        <th class="text-right">Chiffre</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($commercialSalesByMonth as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell font-mono text-xs">{{ $row['id'] }}</td>
                            <td class="admin-table-cell font-medium">{{ $row['name'] }}</td>
                            <td class="admin-table-cell text-right">{{ number_format($row['ventes_confi'], 0, ',', ' ') }}</td>
                            <td class="admin-table-cell text-right text-red-600 dark:text-red-400">{{ number_format($row['ventes_annu'], 0, ',', ' ') }}</td>
                            <td class="admin-table-cell text-right text-orange-600 dark:text-orange-400">{{ number_format($row['ventes_retour'], 0, ',', ' ') }}</td>
                            <td class="admin-table-cell text-right font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ number_format($row['chiffre_realise'], 2, ',', ' ') }} DH</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-slate-500 dark:text-slate-400">Aucune vente pour ce mois</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <x-admin.data-table compact>
                <x-slot:header>Articles les plus vendus — {{ $monthLabel }}</x-slot:header>
                    <thead>
                        <tr>
                            <th class="text-left w-10">#</th>
                            <th class="text-left">Article</th>
                            <th class="text-right">Qté vendue</th>
                            <th class="text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($topProductsByMonth as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="admin-table-cell text-slate-500">{{ $row['rank'] }}</td>
                                <td class="admin-table-cell font-medium">{{ $row['product_name'] }}</td>
                                <td class="admin-table-cell text-right">{{ number_format($row['quantity_sold'], 0, ',', ' ') }}</td>
                                <td class="admin-table-cell text-right">{{ number_format($row['amount'], 2, ',', ' ') }} DH</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-slate-500 dark:text-slate-400">Aucun article vendu ce mois</td>
                            </tr>
                        @endforelse
                    </tbody>
            </x-admin.data-table>

            <x-admin.data-table compact>
                <x-slot:header>Villes actives — {{ $monthLabel }}</x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">Ville</th>
                        <th class="text-right">Commandes</th>
                        <th class="text-right">Chiffre</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($activeCitiesByMonth as $row)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell font-medium">{{ $row['city'] }}</td>
                            <td class="admin-table-cell text-right">{{ number_format($row['orders_count'], 0, ',', ' ') }}</td>
                            <td class="admin-table-cell text-right font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} DH</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-slate-500 dark:text-slate-400">Aucune ville active ce mois</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = () => document.documentElement.classList.contains('dark');
    const chartColors = {
        validated: '#22c55e',
        pending: '#3b82f6',
        cancelled: '#ef4444',
        returns: '#f97316',
        brand: '#2563eb',
        brandLight: 'rgba(37, 99, 235, 0.08)',
    };
    const gridColor = () => isDark() ? '#334155' : '#f1f5f9';
    const tickColor = () => isDark() ? '#94a3b8' : '#64748b';
    const legendColor = () => isDark() ? '#cbd5e1' : '#475569';

    const base = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
    };
    const scaleDefaults = {
        ticks: { color: tickColor() },
        grid: { color: gridColor() },
    };

    new Chart(document.getElementById('orderDistributionChart'), {
        type: 'bar',
        data: {
            labels: @json($orderDistributionChart['labels']),
            datasets: [
                { label: 'Validées', data: @json($orderDistributionChart['validated']), backgroundColor: chartColors.validated, borderRadius: 3, borderSkipped: false, maxBarThickness: 14 },
                { label: 'En attente', data: @json($orderDistributionChart['pending']), backgroundColor: chartColors.pending, borderRadius: 3, borderSkipped: false, maxBarThickness: 14 },
                { label: 'Annulées', data: @json($orderDistributionChart['cancelled']), backgroundColor: chartColors.cancelled, borderRadius: 3, borderSkipped: false, maxBarThickness: 14 },
                { label: 'Retours', data: @json($orderDistributionChart['returns']), backgroundColor: chartColors.returns, borderRadius: 3, borderSkipped: false, maxBarThickness: 14 },
            ],
        },
        options: {
            ...base,
            datasets: {
                bar: {
                    categoryPercentage: 0.7,
                    barPercentage: 0.85,
                },
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 }, color: legendColor() },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, autoSkip: false, maxRotation: 0, minRotation: 0, color: tickColor() },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor() },
                    ticks: { font: { size: 11 }, stepSize: 1, precision: 0, color: tickColor() },
                },
            },
        },
    });

    const commercialLabels = @json(array_column($commercialSalesByMonth, 'name'));
    const commercialTotals = @json(array_column($commercialSalesByMonth, 'chiffre_realise'));

    if (document.getElementById('commercialChart')) {
        new Chart(document.getElementById('commercialChart'), {
            type: 'bar',
            data: {
                labels: commercialLabels.length ? commercialLabels : ['—'],
                datasets: [{
                    label: 'Chiffre réalisé (DH)',
                    data: commercialTotals.length ? commercialTotals : [0],
                    backgroundColor: chartColors.brand,
                    borderRadius: 6,
                    maxBarThickness: 48,
                }],
            },
            options: {
                ...base,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ' ' + new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(ctx.raw) + ' DH',
                        },
                    },
                },
                scales: {
                    x: { ticks: { font: { size: 11 }, color: tickColor() }, grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 10 },
                            maxTicksLimit: 5,
                            color: tickColor(),
                            callback: (v) => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v),
                        },
                        grid: { color: gridColor() },
                    },
                },
            },
        });
    }
});
</script>
@endpush
</x-admin-layout>
