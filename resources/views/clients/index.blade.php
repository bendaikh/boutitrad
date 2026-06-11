<x-admin-layout title="Clients">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <form method="GET" class="flex gap-2 flex-1 max-w-lg">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="flex-1 rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
            <select name="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Tous</option>
                <option value="active" @selected(request('status') === 'active')>Actifs</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option>
            </select>
            <button type="submit" class="px-4 py-2 btn-dark">Filtrer</button>
        </form>
        <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">+ Nouveau client</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">Nom</th>
                    <th class="px-5 py-3 text-left font-medium">Contact</th>
                    <th class="px-5 py-3 text-left font-medium">Ville</th>
                    <th class="px-5 py-3 text-right font-medium">Solde</th>
                    <th class="px-5 py-3 text-center font-medium">Commandes</th>
                    <th class="px-5 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($clients as $client)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium"><a href="{{ route('clients.show', $client) }}" class="text-brand-600">{{ $client->name }}</a></td>
                        <td class="px-5 py-3">{{ $client->phone ?? $client->email ?? '-' }}</td>
                        <td class="px-5 py-3">{{ $client->city ?? '-' }}</td>
                        <td class="px-5 py-3 text-right {{ $client->balance < 0 ? 'text-red-600' : '' }}">{{ number_format($client->balance, 2, ',', ' ') }} DH</td>
                        <td class="px-5 py-3 text-center">{{ $client->orders_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clients.edit', $client) }}" class="text-brand-600 hover:underline mr-3">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun client</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($clients->hasPages())<div class="px-5 py-3 border-t">{{ $clients->links() }}</div>@endif
    </div>
</x-admin-layout>
