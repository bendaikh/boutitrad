<x-admin-layout title="Tableau de bord">

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
                <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg {{ $alert['type'] === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-brand-50 text-brand-800 border border-brand-200' }}">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $alert['message'] }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Main chart --}}
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-800">Distribution des Commandes</h3>
            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            </div>
        </div>
        <div class="h-72 sm:h-80"><canvas id="orderDistributionChart"></canvas></div>
    </div>

    {{-- Secondary content --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="admin-card p-4">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Commandes par statut</h3>
                <div class="h-44"><canvas id="orderStatusChart"></canvas></div>
            </div>
            <div class="admin-card p-4">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Ventes mensuelles</h3>
                <div class="h-44"><canvas id="monthlySalesChart"></canvas></div>
            </div>
            @if($user->isSuperAdmin())
                <div class="admin-card p-4">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">Commerciaux</h3>
                    <div class="h-40"><canvas id="commercialChart"></canvas></div>
                </div>
                <div class="admin-card p-4">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">Livreurs</h3>
                    <div class="h-40"><canvas id="livreurChart"></canvas></div>
                </div>
            @endif
        </div>

        <div class="admin-card flex flex-col max-h-[420px] xl:max-h-none">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-bold text-slate-800">Activité récente</h3>
                <a href="{{ route('orders.index') }}" class="text-xs link-brand">Tout voir</a>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse($recentOrders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between gap-2 px-5 py-3 hover:bg-slate-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium link-brand truncate">{{ $order->reference }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $order->client->name }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-semibold text-slate-800">{{ number_format($order->total, 0, ',', ' ') }} DH</div>
                            <div class="mt-0.5"><x-admin.status-badge :status="$order->status" /></div>
                        </div>
                    </a>
                @empty
                    <p class="px-5 py-8 text-sm text-slate-500 text-center">Aucune commande</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartColors = {
        validated: '#22c55e',
        pending: '#f97316',
        cancelled: '#eab308',
        returns: '#ef4444',
        brand: '#2563eb',
        brandLight: 'rgba(37, 99, 235, 0.08)',
    };

    const base = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
    };

    new Chart(document.getElementById('orderDistributionChart'), {
        type: 'bar',
        data: {
            labels: @json($orderDistributionChart['labels']),
            datasets: [
                { label: 'Validées', data: @json($orderDistributionChart['validated']), backgroundColor: chartColors.validated, borderRadius: 4, borderSkipped: false },
                { label: 'En attente', data: @json($orderDistributionChart['pending']), backgroundColor: chartColors.pending, borderRadius: 4, borderSkipped: false },
                { label: 'Annulées', data: @json($orderDistributionChart['cancelled']), backgroundColor: chartColors.cancelled, borderRadius: 4, borderSkipped: false },
                { label: 'Retours', data: @json($orderDistributionChart['returns']), backgroundColor: chartColors.returns, borderRadius: 4, borderSkipped: false },
            ],
        },
        options: {
            ...base,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', padding: 20, font: { size: 12 } },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, maxTicksLimit: 6 } },
            },
        },
    });

    new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: @json(array_keys($orderStatusChart)),
            datasets: [{
                data: @json(array_values($orderStatusChart)),
                backgroundColor: ['#2563eb','#3b82f6','#22c55e','#eab308','#06b6d4','#ef4444','#f97316','#8b5cf6'],
                borderWidth: 0,
            }],
        },
        options: {
            ...base,
            cutout: '65%',
            plugins: {
                legend: { display: true, position: 'right', labels: { boxWidth: 10, font: { size: 10 }, padding: 8 } },
            },
        },
    });

    new Chart(document.getElementById('monthlySalesChart'), {
        type: 'line',
        data: {
            labels: @json(array_keys($monthlySalesChart)),
            datasets: [{
                data: @json(array_values($monthlySalesChart)),
                borderColor: chartColors.brand,
                backgroundColor: chartColors.brandLight,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                borderWidth: 2,
            }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 6 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 5 }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    @if($user->isSuperAdmin())
    new Chart(document.getElementById('commercialChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($commercialPerformance, 'name')),
            datasets: [{ data: @json(array_column($commercialPerformance, 'total')), backgroundColor: chartColors.brand, borderRadius: 4 }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 4 }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    new Chart(document.getElementById('livreurChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($livreurPerformance, 'name')),
            datasets: [{ data: @json(array_column($livreurPerformance, 'count')), backgroundColor: chartColors.validated, borderRadius: 4 }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 4 }, grid: { color: '#f1f5f9' } },
            },
        },
    });
    @endif
});
</script>
@endpush
</x-admin-layout>
