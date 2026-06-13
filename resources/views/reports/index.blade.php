<x-admin-layout title="Rapports">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-admin.stat-card label="Ventes" :value="number_format($salesTotal, 0, ',', ' ').' DH'" color="emerald" />
                <x-admin.stat-card label="Commandes" :value="$ordersCount" color="blue" />
                <x-admin.stat-card label="Clients" :value="$clientsCount" color="indigo" />
                <x-admin.stat-card label="Stock" :value="number_format($stockValue, 0, ',', ' ').' DH'" color="amber" />
            </div>
        </x-slot:toolbar>

        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border p-5 shadow-sm overflow-y-auto min-h-0">
                <h3 class="font-semibold mb-4">Commandes par statut</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($ordersByStatus as $status => $count)
                        <li class="flex justify-between"><span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span><strong>{{ $count }}</strong></li>
                    @endforeach
                </ul>
            </div>
            <div class="flex flex-col min-h-0">
                <h3 class="font-semibold mb-4 shrink-0">Top produits</h3>
                <x-admin.data-table class="flex-1 min-h-0">
                    <thead>
                        <tr>
                            <th class="text-left">Produit</th>
                            <th class="text-right">Quantité</th>
                            <th class="text-right">Chiffre d'affaires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($topProducts as $p)
                            <tr><td class="px-4 py-2">{{ $p->product_name }}</td><td class="px-4 py-2 text-right">{{ $p->qty }} vendus</td><td class="px-4 py-2 text-right font-medium">{{ number_format($p->revenue, 0, ',', ' ') }} DH</td></tr>
                        @endforeach
                    </tbody>
                </x-admin.data-table>
            </div>
        </div>
    </x-admin.list-page>
</x-admin-layout>
