<x-admin-layout title="Rapports">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stat-card label="Ventes" :value="number_format($salesTotal, 0, ',', ' ').' DH'" color="emerald" />
        <x-admin.stat-card label="Commandes" :value="$ordersCount" color="blue" />
        <x-admin.stat-card label="Clients" :value="$clientsCount" color="indigo" />
        <x-admin.stat-card label="Stock" :value="number_format($stockValue, 0, ',', ' ').' DH'" color="amber" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Commandes par statut</h3>
            <ul class="space-y-2 text-sm">
                @foreach($ordersByStatus as $status => $count)
                    <li class="flex justify-between"><span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span><strong>{{ $count }}</strong></li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Top produits</h3>
            <table class="w-full text-sm">
                @foreach($topProducts as $p)
                    <tr class="border-b"><td class="py-2">{{ $p->product_name }}</td><td class="py-2 text-right">{{ $p->qty }} vendus</td><td class="py-2 text-right font-medium">{{ number_format($p->revenue, 0, ',', ' ') }} DH</td></tr>
                @endforeach
            </table>
        </div>
    </div>
</x-admin-layout>
