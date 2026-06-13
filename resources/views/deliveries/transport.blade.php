<x-admin-layout title="Transport">
    <x-admin.list-page>
        <x-admin.data-table class="flex-1 min-h-0">
            @if($orders->hasPages())
                <x-slot:footer>{{ $orders->links() }}</x-slot:footer>
            @endif
            <thead><tr>
                <th class="text-left">Référence</th><th class="text-left">Client</th>
                <th class="text-left">Livreur</th><th class="text-left">Statut</th><th class="text-left">Date</th>
            </tr></thead>
            <tbody class="divide-y">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3"><a href="{{ route('orders.show', $order) }}" class="text-brand-600 font-medium">{{ $order->reference }}</a></td>
                        <td class="px-5 py-3">{{ $order->client->name }}</td>
                        <td class="px-5 py-3">{{ $order->livreur?->name ?? 'Non assigné' }}</td>
                        <td class="px-5 py-3"><x-admin.status-badge :status="$order->status" /></td>
                        <td class="px-5 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-3 text-center text-slate-500 dark:text-slate-400">Aucun transport en cours</td></tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
