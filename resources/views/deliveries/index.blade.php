<x-admin-layout title="Livraisons">
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-5 py-3 text-left">Référence</th><th class="px-5 py-3 text-left">Client</th>
                <th class="px-5 py-3 text-left">Livreur</th><th class="px-5 py-3 text-left">Statut</th><th class="px-5 py-3 text-left">Date</th>
            </tr></thead>
            <tbody class="divide-y">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-indigo-600 font-medium">{{ $order->reference }}</a></td>
                        <td class="px-5 py-3">{{ $order->client->name }}</td>
                        <td class="px-5 py-3">{{ $order->livreur?->name ?? 'Non assigné' }}</td>
                        <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                        <td class="px-5 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucune livraison</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())<div class="px-5 py-3 border-t">{{ $orders->links() }}</div>@endif
    </div>
</x-admin-layout>
