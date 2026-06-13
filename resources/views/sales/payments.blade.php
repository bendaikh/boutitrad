<x-admin-layout title="Paiements">
    <x-admin.list-page>
        <x-admin.data-table class="flex-1 min-h-0">
            @if($transactions->hasPages())
                <x-slot:footer>{{ $transactions->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-left">Date</th>
                    <th class="text-left">Libellé</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3">{{ $transaction->description ?? 'Paiement' }}</td>
                        <td class="px-5 py-3 text-right font-medium text-emerald-600">{{ number_format($transaction->amount, 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun paiement enregistré</td></tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
