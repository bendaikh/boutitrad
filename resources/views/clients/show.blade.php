<x-admin-layout title="{{ $client->name }}">
    <x-admin.list-page>
        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-4 shrink-0 overflow-y-auto">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-20 h-20 rounded-full border border-slate-200 bg-slate-50 overflow-hidden shrink-0 flex items-center justify-center text-slate-400">
                            @if($client->photoUrl())
                                <img src="{{ $client->photoUrl() }}" alt="{{ $client->name }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="font-semibold text-lg truncate">{{ $client->name }}</h2>
                                <a href="{{ route('clients.edit', $client) }}" class="text-sm text-brand-600 shrink-0">Modifier</a>
                            </div>
                            @if($client->is_active)
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Actif</span>
                            @else
                                <span class="inline-flex mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inactif</span>
                            @endif
                        </div>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-slate-500 dark:text-slate-400">ID client</dt><dd class="font-mono">{{ $client->formattedId() }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Contact</dt><dd>{{ $client->phone ?? $client->email ?? '-' }}</dd></div>
                        @if($client->facebook_page)
                            <div><dt class="text-slate-500 dark:text-slate-400">Facebook</dt><dd class="truncate">{{ $client->facebook_page }}</dd></div>
                        @endif
                        @if($client->instagram_page)
                            <div><dt class="text-slate-500 dark:text-slate-400">Instagram</dt><dd class="truncate">{{ $client->instagram_page }}</dd></div>
                        @endif
                        <div><dt class="text-slate-500 dark:text-slate-400">Ville</dt><dd>{{ $client->city ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Prospection</dt><dd>{{ $client->prospection?->label() ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Mode paiement</dt><dd>{{ $client->payment_mode?->label() ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Commercial affecté</dt><dd>{{ $client->commercial?->name ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Adresse</dt><dd>{{ $client->address ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Solde</dt><dd class="font-semibold {{ $client->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($client->balance, 2, ',', ' ') }} DH</dd></div>
                        <div><dt class="text-slate-500 dark:text-slate-400">Total achats</dt><dd class="font-semibold">{{ number_format($client->totalPurchases(), 2, ',', ' ') }} DH</dd></div>
                    </dl>
                    @if($client->notes)
                        <div class="mt-4 pt-4 border-t"><p class="text-sm text-slate-600">{{ $client->notes }}</p></div>
                    @endif
                </div>
            </div>
            <x-admin.data-table class="lg:col-span-2 flex-1 min-h-0">
                <x-slot:header>Historique des commandes</x-slot:header>
                <thead><tr>
                    <th class="text-left">Référence</th>
                    <th class="text-left">Statut</th>
                    <th class="text-right">Total</th>
                    <th class="text-left">Date</th>
                </tr></thead>
                <tbody class="admin-table-body">
                    @forelse($client->orders as $order)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-brand-600">{{ $order->reference }}</a></td>
                            <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                            <td class="px-5 py-3 text-right">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                            <td class="px-5 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500 dark:text-slate-400">Aucune commande</td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </x-admin.list-page>
</x-admin-layout>
