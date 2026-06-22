<div
    x-show="commercialId && payMonth"
    x-cloak
    class="{{ $wrapperClass ?? '' }}"
>
    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-2">
        Commandes confirmées du mois sélectionné
        <span x-show="loadingStats" class="text-xs font-normal text-slate-500">(calcul…)</span>
    </h3>
    <p x-show="!loadingStats && payrollOrders.length === 0" class="text-sm text-slate-500 dark:text-slate-400 py-4 text-center">
        Aucune commande confirmée pour ce mois.
    </p>
    <div x-show="payrollOrders.length > 0" class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-100 dark:bg-slate-800">
                <tr>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Réf bon</th>
                    <th class="px-3 py-2 text-left">Réf Cathedis</th>
                    <th class="px-3 py-2 text-left">Statut</th>
                    <th class="px-3 py-2 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <template x-for="(orderRow, index) in payrollOrders" :key="index">
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-3 py-2 whitespace-nowrap" x-text="orderRow.date"></td>
                        <td class="px-3 py-2 font-mono" x-text="orderRow.reference"></td>
                        <td class="px-3 py-2 font-mono" x-text="orderRow.delivery_ref || '—'"></td>
                        <td class="px-3 py-2" x-text="orderRow.status"></td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium" x-text="formatMoney(orderRow.total)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-700">
                <tr>
                    <td colspan="4" class="px-3 py-2 text-right font-semibold">Total commandes confirmées</td>
                    <td class="px-3 py-2 text-right tabular-nums font-bold text-emerald-700 dark:text-emerald-400" x-text="formatMoney(revenue)"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
        Commission calculée sur le total des commandes confirmées, en préparation, expédiées ou livrées du mois.
    </p>
</div>
