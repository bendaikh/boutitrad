<x-admin-layout title="Tableau de bord">

@php $user = auth()->user(); @endphp

<div class="space-y-4 max-w-[1600px]">
    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <x-admin.stat-card compact label="CA" :value="number_format($stats['revenue'], 0, ',', ' ').' DH'" color="indigo" />
        <x-admin.stat-card compact label="Commandes" :value="$stats['total_orders']" color="blue" />
        <x-admin.stat-card compact label="Charges" :value="number_format($stats['expenses'], 0, ',', ' ').' DH'" color="rose" />
        <x-admin.stat-card compact label="Bénéfice" :value="number_format($stats['net_profit'], 0, ',', ' ').' DH'" color="emerald" />
        <x-admin.stat-card compact label="Trésorerie" :value="number_format($stats['treasury'], 0, ',', ' ').' DH'" color="cyan" />
        <x-admin.stat-card compact label="Clients" :value="$stats['clients_count']" color="purple" />
    </div>

    {{-- Alerts (compact) --}}
    @if(count($alerts))
        <div class="flex flex-wrap gap-2">
            @foreach(array_slice($alerts, 0, 3) as $alert)
                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg {{ $alert['type'] === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $alert['message'] }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Main content: charts + recent activity --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        {{-- Charts --}}
        <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800 mb-2">Commandes par statut</h3>
                <div class="h-40"><canvas id="orderStatusChart"></canvas></div>
            </div>
            <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-800 mb-2">Ventes mensuelles</h3>
                <div class="h-40"><canvas id="monthlySalesChart"></canvas></div>
            </div>
            @if($user->isSuperAdmin())
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">Commerciaux</h3>
                    <div class="h-36"><canvas id="commercialChart"></canvas></div>
                </div>
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">Livreurs</h3>
                    <div class="h-36"><canvas id="livreurChart"></canvas></div>
                </div>
            @endif
        </div>

        {{-- Recent activity sidebar --}}
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col max-h-[420px] xl:max-h-none">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between shrink-0">
                <h3 class="text-sm font-semibold text-slate-800">Activité récente</h3>
                <a href="{{ route('orders.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Tout voir</a>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto flex-1">
                @forelse($recentOrders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between gap-2 px-4 py-2.5 hover:bg-slate-50 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-indigo-600 truncate">{{ $order->reference }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $order->client->name }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-medium">{{ number_format($order->total, 0, ',', ' ') }} DH</div>
                            <div class="mt-0.5"><x-admin.status-badge :status="$order->status" /></div>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-6 text-sm text-slate-500 text-center">Aucune commande</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
    };

    new Chart(document.getElementById('orderStatusChart'), {
        type: 'doughnut',
        data: {
            labels: @json(array_keys($orderStatusChart)),
            datasets: [{
                data: @json(array_values($orderStatusChart)),
                backgroundColor: ['#6366f1','#3b82f6','#10b981','#f59e0b','#06b6d4','#ef4444','#f97316','#8b5cf6'],
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
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                borderWidth: 2,
            }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 6 } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 5 } },
            },
        },
    });

    @if($user->isSuperAdmin())
    new Chart(document.getElementById('commercialChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($commercialPerformance, 'name')),
            datasets: [{ data: @json(array_column($commercialPerformance, 'total')), backgroundColor: '#6366f1', borderRadius: 4 }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 4 } },
            },
        },
    });

    new Chart(document.getElementById('livreurChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($livreurPerformance, 'name')),
            datasets: [{ data: @json(array_column($livreurPerformance, 'count')), backgroundColor: '#10b981', borderRadius: 4 }],
        },
        options: {
            ...base,
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, ticks: { font: { size: 10 }, maxTicksLimit: 4 } },
            },
        },
    });
    @endif
});
</script>
@endpush
</x-admin-layout>
