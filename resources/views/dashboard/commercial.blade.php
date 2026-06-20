<x-admin-layout :title="$commercial->name">
    @php
        $isOwnDashboard = auth()->id() === $commercial->id;
        $dashboardTitle = $isOwnDashboard ? 'Mon tableau de bord' : 'Tableau de bord — '.$commercial->name;
    @endphp

    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $dashboardTitle }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $commercial->formattedCommercialId() }} · {{ $commercial->email }}
                        </p>
                    </div>
                    @if($isOwnDashboard)
                        <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Nouvelle commande
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-admin.stat-card compact label="Mes commandes" :value="number_format($stats['orders_count'], 0, ',', ' ')" color="blue" />
                    <x-admin.stat-card compact label="Mes clients" :value="number_format($stats['clients_count'], 0, ',', ' ')" color="cyan" />
                    <x-admin.stat-card compact label="Produits actifs" :value="number_format($stats['products_count'], 0, ',', ' ')" color="purple" />
                    <x-admin.stat-card compact label="Stock total" :value="number_format($stats['real_stock_qty'], 0, ',', ' ')" color="brand" />
                </div>
            </div>
        </x-slot:toolbar>

        <div class="flex-1 min-h-0 flex flex-col gap-5 overflow-y-auto">
            {{-- Commandes --}}
            <x-admin.data-table compact min-width="1100px" class="shrink-0">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <span>Mes commandes</span>
                        <a href="{{ route('orders.index') }}" class="text-xs font-medium text-brand-600 dark:text-brand-400 hover:underline">Voir tout</a>
                    </div>
                </x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th class="text-left">Réf bon</th>
                        <th class="text-left">Réf livraison</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Ville</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell whitespace-nowrap">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="admin-table-cell">
                                <a href="{{ route('orders.bon', $order) }}" class="link-brand font-medium font-mono text-xs">{{ $order->reference }}</a>
                            </td>
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $order->deliveryReference() ?? '—' }}</td>
                            <td class="admin-table-cell font-medium">{{ $order->client?->name ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $order->client?->deliveryCityName() ?: '—' }}</td>
                            <td class="admin-table-cell text-center">
                                <x-admin.status-badge :status="$order->status" />
                            </td>
                            <td class="admin-table-cell text-right tabular-nums font-medium">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune commande</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>

            {{-- Stock --}}
            <x-admin.data-table compact min-width="900px" class="shrink-0">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <span>Consultation du stock</span>
                        <a href="{{ route('stock.index') }}" class="text-xs font-medium text-brand-600 dark:text-brand-400 hover:underline">Voir tout</a>
                    </div>
                </x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left w-16">Photo</th>
                        <th class="text-left">Réf prod</th>
                        <th class="text-left">Désignation prod</th>
                        <th class="text-center">Catégorie</th>
                        <th class="text-center">Quantité</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($stockProducts as $product)
                        @php
                            $statusClass = match ($product->stockStatus()) {
                                'dispo' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'faible' => 'bg-yellow-300 text-yellow-900 dark:bg-yellow-900/50 dark:text-yellow-200 font-bold',
                                'rupture' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-semibold',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell py-2">
                                @if($product->imageUrl())
                                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-600">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </td>
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $product->sku }}</td>
                            <td class="admin-table-cell font-medium">{{ $product->name }}</td>
                            <td class="admin-table-cell text-center">{{ $product->category?->name ?? '—' }}</td>
                            <td class="admin-table-cell text-center tabular-nums">{{ $product->quantity }}</td>
                            <td class="admin-table-cell text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $product->stockStatusLabel() }}
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

            {{-- Clients --}}
            <x-admin.data-table compact min-width="920px" class="shrink-0">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <span>Mes clients</span>
                        <a href="{{ route('clients.index') }}" class="text-xs font-medium text-brand-600 dark:text-brand-400 hover:underline">Voir tout</a>
                    </div>
                </x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">ID client</th>
                        <th class="text-left">Nom client</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Ville</th>
                        <th class="text-left">Mode paiement</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $client->formattedId() }}</td>
                            <td class="admin-table-cell font-medium">{{ $client->name }}</td>
                            <td class="admin-table-cell">{{ $client->phone ?: ($client->email ?: '—') }}</td>
                            <td class="admin-table-cell">{{ $client->deliveryCityName() ?: '—' }}</td>
                            <td class="admin-table-cell">{{ $client->payment_mode?->label() ?? '—' }}</td>
                            <td class="admin-table-cell text-center">
                                <span @class([
                                    'inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    $client->is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                ])>
                                    {{ $client->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun client affecté à ce commercial</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </x-admin.list-page>
</x-admin-layout>
