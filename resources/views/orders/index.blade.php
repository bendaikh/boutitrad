<x-admin-layout title="Commandes">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <form method="GET" class="flex gap-2 flex-wrap flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="rounded-lg border-slate-300 text-sm">
            <select name="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Tous statuts</option>
                @foreach($statuses as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm">Filtrer</button>
        </form>
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isCommercial())
            <a href="{{ route('orders.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Nouvelle commande</a>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-5 py-3 text-left font-medium">Référence</th>
                <th class="px-5 py-3 text-left font-medium">Client</th>
                <th class="px-5 py-3 text-left font-medium">Commercial</th>
                <th class="px-5 py-3 text-left font-medium">Statut</th>
                <th class="px-5 py-3 text-right font-medium">Total</th>
                <th class="px-5 py-3 text-left font-medium">Date</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-indigo-600 font-medium">{{ $order->reference }}</a></td>
                        <td class="px-5 py-3">{{ $order->client->name }}</td>
                        <td class="px-5 py-3">{{ $order->commercial?->name ?? '-' }}</td>
                        <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                        <td class="px-5 py-3 text-right font-medium">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                        <td class="px-5 py-3 text-slate-500">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucune commande</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())<div class="px-5 py-3 border-t">{{ $orders->links() }}</div>@endif
    </div>
</x-admin-layout>
