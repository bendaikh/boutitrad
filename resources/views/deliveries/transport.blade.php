<x-admin-layout title="Transport">
    <x-admin.list-page>
        <x-admin.data-table class="flex-1 min-h-0">
            @if($orders->hasPages())
                <x-slot:footer>{{ $orders->links() }}</x-slot:footer>
            @endif
            <thead><tr>
                <th class="text-left">Réf Bon</th>
                <th class="text-left">Réf livraison</th>
                <th class="text-left">Client</th>
                <th class="text-left">Partenaire</th>
                <th class="text-left">Statut système</th>
                <th class="text-left">Statut Cathedis</th>
                <th class="text-right">Montant</th>
                <th class="text-left">Envoi partenaire</th>
                <th class="text-right">Action</th>
            </tr></thead>
            <tbody class="divide-y">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="admin-table-cell font-mono text-xs font-medium">{{ $order->reference }}</td>
                        <td class="admin-table-cell font-mono text-xs">{{ $order->deliveryReference() ?? '—' }}</td>
                        <td class="admin-table-cell">{{ $order->client->name }}</td>
                        <td class="admin-table-cell">{{ $order->deliveryPartner?->name ?? '—' }}</td>
                        <td class="admin-table-cell"><x-admin.status-badge :status="$order->status" /></td>
                        <td class="admin-table-cell"><x-admin.cathedis-status-badge :order="$order" compact /></td>
                        <td class="admin-table-cell text-right">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                        <td class="admin-table-cell">{{ $order->sent_to_partner_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="admin-table-cell text-right">
                            <a href="{{ route('deliveries.orders.show', $order) }}" class="text-brand-600 text-sm font-medium hover:underline">Traiter</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun transport en cours</td></tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
