<x-admin-layout title="{{ $client->name }}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-lg">{{ $client->name }}</h2>
                    <a href="{{ route('clients.edit', $client) }}" class="text-sm text-indigo-600">Modifier</a>
                </div>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-slate-500">Email</dt><dd>{{ $client->email ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd>{{ $client->phone ?? '-' }}</dd></div>
                    <div><dt class="text-slate-500">Adresse</dt><dd>{{ $client->address ?? '-' }}, {{ $client->city ?? '' }}</dd></div>
                    <div><dt class="text-slate-500">Solde</dt><dd class="font-semibold {{ $client->balance < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ number_format($client->balance, 2, ',', ' ') }} DH</dd></div>
                    <div><dt class="text-slate-500">Total achats</dt><dd class="font-semibold">{{ number_format($client->totalPurchases(), 2, ',', ' ') }} DH</dd></div>
                </dl>
                @if($client->notes)
                    <div class="mt-4 pt-4 border-t"><p class="text-sm text-slate-600">{{ $client->notes }}</p></div>
                @endif
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b"><h3 class="font-semibold">Historique des commandes</h3></div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50"><tr>
                        <th class="px-5 py-3 text-left">Référence</th>
                        <th class="px-5 py-3 text-left">Statut</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-left">Date</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($client->orders as $order)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-indigo-600">{{ $order->reference }}</a></td>
                                <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                                <td class="px-5 py-3 text-right">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                                <td class="px-5 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">Aucune commande</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
