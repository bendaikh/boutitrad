<x-admin-layout title="{{ $commercial->name }}">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <x-admin.stat-card label="CA vendu" :value="number_format($totalSales, 0, ',', ' ').' DH'" color="indigo" />
                    <x-admin.stat-card label="Commandes" :value="$ordersCount" color="blue" />
                    <x-admin.stat-card label="Commissions" :value="number_format($totalCommissions, 0, ',', ' ').' DH'" color="emerald" />
                    <x-admin.stat-card label="Taux commission" :value="number_format($commissionRate, 1, ',', ' ').' %'" color="purple" />
                </div>
                @if($objectives->count())
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                        <h3 class="font-semibold mb-3 text-slate-800 dark:text-slate-100">Objectifs</h3>
                        @foreach($objectives as $obj)
                            <div class="mb-3">
                                <div class="flex justify-between text-sm mb-1 text-slate-700 dark:text-slate-300">
                                    <span>{{ number_format($obj->achieved_amount, 0, ',', ' ') }} / {{ number_format($obj->target_amount, 0, ',', ' ') }} DH</span>
                                    <span>{{ round($obj->progressPercent()) }}%</span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                    <div class="bg-brand-600 h-2 rounded-full" style="width: {{ $obj->progressPercent() }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-slot:toolbar>

        <div class="flex-1 min-h-0 flex flex-col gap-6">
            <x-admin.data-table class="flex-1 min-h-0">
                <x-slot:header>Commandes vendues (livrées)</x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">Réf.</th>
                        <th class="text-left">Client</th>
                        <th class="text-right">Total commande</th>
                        <th class="text-right">Commission</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($deliveredOrders as $order)
                        @php
                            $orderCommission = $commissions->firstWhere('order_id', $order->id);
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <a href="{{ route('orders.show', $order) }}" class="text-brand-600 hover:underline">{{ $order->reference }}</a>
                            </td>
                            <td class="px-5 py-3">{{ $order->client->name }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format($order->total, 2, ',', ' ') }} DH</td>
                            <td class="px-5 py-3 text-right tabular-nums text-emerald-700 dark:text-emerald-400">
                                @if($orderCommission)
                                    {{ number_format($orderCommission->amount, 2, ',', ' ') }} DH
                                    <span class="text-xs text-slate-500 dark:text-slate-400">({{ number_format($orderCommission->rate, 1, ',', ' ') }} %)</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune commande livrée</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>

            <x-admin.data-table class="flex-1 min-h-0">
                <x-slot:header>Historique des commissions</x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">Commande</th>
                        <th class="text-right">Montant commande</th>
                        <th class="text-right">Taux</th>
                        <th class="text-right">Commission</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($commissions as $commission)
                        <tr>
                            <td class="px-5 py-3">
                                @if($commission->order)
                                    <a href="{{ route('orders.show', $commission->order) }}" class="text-brand-600 hover:underline">{{ $commission->order->reference }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ $commission->order ? number_format($commission->order->total, 2, ',', ' ') . ' DH' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format($commission->rate, 1, ',', ' ') }} %</td>
                            <td class="px-5 py-3 text-right tabular-nums font-medium text-emerald-700 dark:text-emerald-400">
                                {{ number_format($commission->amount, 2, ',', ' ') }} DH
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucune commission. Les commissions sont calculées automatiquement sur les commandes livrées.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </x-admin.list-page>
</x-admin-layout>
