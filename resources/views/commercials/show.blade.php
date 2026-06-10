<x-admin-layout title="{{ $commercial->name }}">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <x-admin.stat-card label="CA total" :value="number_format($totalSales, 0, ',', ' ').' DH'" color="indigo" />
        <x-admin.stat-card label="Commandes" :value="$ordersCount" color="blue" />
        <x-admin.stat-card label="Commissions" :value="number_format($totalCommissions, 0, ',', ' ').' DH'" color="emerald" />
        <x-admin.stat-card label="Objectifs" :value="count($objectives)" color="purple" />
    </div>
    @if($objectives->count())
        <div class="bg-white rounded-xl border p-5 mb-6 shadow-sm">
            <h3 class="font-semibold mb-3">Objectifs</h3>
            @foreach($objectives as $obj)
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1"><span>{{ number_format($obj->achieved_amount, 0, ',', ' ') }} / {{ number_format($obj->target_amount, 0, ',', ' ') }} DH</span><span>{{ round($obj->progressPercent()) }}%</span></div>
                    <div class="w-full bg-slate-200 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $obj->progressPercent() }}%"></div></div>
                </div>
            @endforeach
        </div>
    @endif
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b font-semibold">Commandes récentes</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Réf.</th><th class="px-5 py-3 text-left">Client</th><th class="px-5 py-3 text-left">Statut</th><th class="px-5 py-3 text-right">Total</th></tr></thead>
            <tbody class="divide-y">
                @foreach($orders as $order)
                    <tr><td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-indigo-600">{{ $order->reference }}</a></td><td class="px-5 py-3">{{ $order->client->name }}</td><td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td><td class="px-5 py-3 text-right">{{ number_format($order->total, 2, ',', ' ') }} DH</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
