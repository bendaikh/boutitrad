<x-admin-layout title="Balance client">
    <x-admin.list-page>
        <x-slot:toolbar>
            <form method="GET" class="admin-panel p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div>
                        <label for="client_id" class="block text-xs font-medium text-slate-600 mb-1">ID client</label>
                        <input type="text" id="client_id" name="client_id" value="{{ request('client_id') }}" placeholder="Ex. CL-00001" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label for="client_name" class="block text-xs font-medium text-slate-600 mb-1">Nom client</label>
                        <input type="text" id="client_name" name="client_name" value="{{ request('client_name') }}" placeholder="Rechercher par nom..." class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label for="order_date" class="block text-xs font-medium text-slate-600 mb-1">Date commande</label>
                        <input type="date" id="order_date" name="order_date" value="{{ request('order_date') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label for="amount" class="block text-xs font-medium text-slate-600 mb-1">Montant commande</label>
                        <input type="text" id="amount" name="amount" value="{{ request('amount') }}" placeholder="Ex. 1500,00" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="px-4 py-2 btn-dark text-sm">Filtrer</button>
                    @if(request()->hasAny(['client_id', 'client_name', 'order_date', 'amount']))
                        <a href="{{ route('clients.balances') }}" class="px-4 py-2 btn-secondary text-sm">Réinitialiser</a>
                    @endif
                </div>
            </form>
        </x-slot:toolbar>

        <x-admin.data-table min-width="960px" class="flex-1 min-h-0">
            @if($orders->hasPages())
                <x-slot:footer>{{ $orders->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-left">ID client</th>
                    <th class="text-left">Nom client</th>
                    <th class="text-left">Date cmd</th>
                    <th class="text-right">Montant cmd</th>
                    <th class="text-left">Type règl.</th>
                    <th class="text-right">Mnt payé</th>
                    <th class="text-right">Solde</th>
                </tr>
            </thead>
            <tbody class="admin-table-body">
                @forelse($orders as $order)
                    @php
                        $montantCmd = $order->orderAmount();
                        $montantPaye = $order->paidAmount();
                        $status = $order->paymentStatus();
                        $solde = $status === 'paid' ? 0 : $montantCmd - $montantPaye;
                        $soldeClass = match ($status) {
                            'paid' => 'text-slate-700',
                            'partial' => 'bg-yellow-300 text-yellow-900 font-bold',
                            'unpaid' => 'bg-red-100 text-red-700 font-semibold',
                        };
                        $printUrl = route('clients.balance.print', $order->client_id);
                    @endphp
                    <tr
                        class="hover:bg-slate-50 cursor-pointer"
                        ondblclick="window.open('{{ $printUrl }}', '_blank')"
                        title="Double-clic pour ouvrir la feuille balance"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $order->client->formattedId() }}</td>
                        <td class="px-4 py-3 font-medium text-brand-700">{{ $order->client->name }}</td>
                        <td class="px-4 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($montantCmd, 2, ',', ' ') }} DH</td>
                        <td class="px-4 py-3">{{ $order->payment_mode?->label() ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($montantPaye, 2, ',', ' ') }} DH</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-block min-w-[7rem] px-2 py-1 rounded {{ $soldeClass }}">
                                {{ number_format($solde, 2, ',', ' ') }} DH
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                            {{ request()->hasAny(['client_id', 'client_name', 'order_date', 'amount']) ? 'Aucune commande ne correspond aux critères de recherche' : 'Aucune commande à afficher' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.data-table>

        <x-slot:footer>
            <div class="flex flex-wrap gap-4 text-xs text-slate-600">
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-red-100 border border-red-200"></span> Impayé — solde = montant commande</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-yellow-300 border border-yellow-400"></span> Partiel — solde jaune = montant cmd − mnt payé</span>
                <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded bg-white border border-slate-200 dark:border-slate-700"></span> Soldé — solde = 0</span>
                <span class="text-slate-400">· Double-clic pour ouvrir la feuille balance</span>
            </div>
        </x-slot:footer>
    </x-admin.list-page>
</x-admin-layout>
