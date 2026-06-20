<x-admin-layout title="Charges" full-height>
    @php
        $formExpense = $editingExpense ?? null;
        $isEdit = (bool) $formExpense;
        $chargeFilters = $chargeFilters ?? [];
    @endphp

    <div
        x-data="{
            selectedId: {{ $selectedExpenseId ?? 'null' }},
            treasuryMode: @js(old('treasury_mode', $formExpense?->treasury_mode?->value ?? 'caisse')),
            isCaisse() {
                return this.treasuryMode === 'caisse';
            },
            onTreasuryChange() {
                if (this.isCaisse()) {
                    ['payment_number', 'bank', 'drawer_name', 'instrument_date'].forEach((name) => {
                        const input = this.$root.querySelector(`[name='${name}']`);
                        if (input) {
                            input.value = '';
                        }
                    });
                }
            },
            openExpense(id) {
                const params = new URLSearchParams({ charge_month: @js($chargeMonth), selected: id });
                window.location.href = '{{ route('charges.index') }}?' + params.toString();
            },
        }"
        class="flex flex-col flex-1 min-h-0 w-full"
    >
        <div class="shrink-0 pb-3 border-b border-slate-200/80 dark:border-slate-800 bg-surface-muted dark:bg-slate-950">
            <form
                method="POST"
                action="{{ $isEdit && $formExpense ? route('charges.expenses.update', $formExpense) : route('charges.expenses.store') }}"
                class="admin-form-shell max-w-full"
            >
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif
                <input type="hidden" name="charge_month" value="{{ $chargeMonth }}">

                <div class="px-3 py-2.5 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-brand-50/80 to-white dark:from-brand-900/25 dark:to-slate-900 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $isEdit ? 'Modifier la charge' : 'Saisie charge' }}</h2>
                    @if($isEdit)
                        <a href="{{ route('charges.index', $chargeFilters) }}" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 shrink-0">Annuler</a>
                    @endif
                </div>

                <div class="admin-order-form-bar">
                    <div class="admin-charge-form-grid">
                        <div class="admin-order-form-field">
                            <label for="expense_date" class="admin-order-form-label">Date</label>
                            <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', $formExpense?->expense_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required class="admin-order-form-input">
                            @error('expense_date')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="admin-order-form-field">
                            <label for="charge_type" class="admin-order-form-label">Type charge</label>
                            <select id="charge_type" name="charge_type" required class="admin-order-form-input">
                                <option value="">— Choisir —</option>
                                @foreach($chargeTypes as $type)
                                    <option value="{{ $type->value }}" @selected(old('charge_type', $formExpense?->charge_type?->value) === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('charge_type')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="admin-order-form-field md:col-span-2">
                            <label for="title" class="admin-order-form-label">Désignation</label>
                            <input type="text" id="title" name="title" value="{{ old('title', $formExpense?->title) }}" required placeholder="Libellé de la charge" class="admin-order-form-input">
                            @error('title')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="admin-order-form-field">
                            <label for="amount" class="admin-order-form-label">Montant</label>
                            <input type="number" step="0.01" min="0" id="amount" name="amount" value="{{ old('amount', $formExpense?->amount) }}" required placeholder="0,00" class="admin-order-form-input tabular-nums">
                            @error('amount')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="admin-order-form-field">
                            <label for="treasury_mode" class="admin-order-form-label">Paiement — Trésorerie</label>
                            <select
                                id="treasury_mode"
                                name="treasury_mode"
                                x-model="treasuryMode"
                                @change="onTreasuryChange()"
                                required
                                class="admin-order-form-input"
                            >
                                @foreach($treasuryModes as $mode)
                                    <option value="{{ $mode->value }}" @selected(old('treasury_mode', $formExpense?->treasury_mode?->value ?? 'caisse') === $mode->value)>{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                            @error('treasury_mode')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div
                        class="admin-charge-instrument-bar mt-3 pt-3 border-t border-slate-200/80 dark:border-slate-700 transition-opacity"
                        :class="isCaisse() ? 'opacity-50' : 'opacity-100'"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">Détails paiement</p>
                        <div class="admin-charge-instrument-grid">
                            <div class="admin-order-form-field">
                                <label for="payment_number" class="admin-order-form-label">N°</label>
                                <input
                                    type="text"
                                    id="payment_number"
                                    name="payment_number"
                                    value="{{ old('payment_number', $formExpense?->payment_number) }}"
                                    :disabled="isCaisse()"
                                    :required="!isCaisse()"
                                    placeholder="N° chèque / virement"
                                    class="admin-order-form-input admin-charge-instrument-input"
                                >
                                @error('payment_number')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="admin-order-form-field">
                                <label for="bank" class="admin-order-form-label">Bnq</label>
                                <select
                                    id="bank"
                                    name="bank"
                                    :disabled="isCaisse()"
                                    :required="!isCaisse()"
                                    class="admin-order-form-input admin-charge-instrument-input"
                                >
                                    <option value="">— Banque —</option>
                                    @foreach($banks as $bank)
                                        @if($bank !== \App\Enums\Bank::Esp)
                                            <option value="{{ $bank->value }}" @selected(old('bank', $formExpense?->bank?->value) === $bank->value)>{{ $bank->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('bank')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="admin-order-form-field">
                                <label for="drawer_name" class="admin-order-form-label">Tiré</label>
                                <input
                                    type="text"
                                    id="drawer_name"
                                    name="drawer_name"
                                    value="{{ old('drawer_name', $formExpense?->drawer_name) }}"
                                    :disabled="isCaisse()"
                                    :required="!isCaisse()"
                                    placeholder="Nom du tiré"
                                    class="admin-order-form-input admin-charge-instrument-input"
                                >
                                @error('drawer_name')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                            </div>
                            <div class="admin-order-form-field">
                                <label for="instrument_date" class="admin-order-form-label">Date</label>
                                <input
                                    type="date"
                                    id="instrument_date"
                                    name="instrument_date"
                                    value="{{ old('instrument_date', $formExpense?->instrument_date?->format('Y-m-d')) }}"
                                    :disabled="isCaisse()"
                                    :required="!isCaisse()"
                                    class="admin-order-form-input admin-charge-instrument-input"
                                >
                                @error('instrument_date')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-product-form-actions">
                    <div class="flex flex-wrap items-center justify-end gap-2 w-full">
                        @if($isEdit)
                            <x-admin.action-btn type="submit" icon="edit" label="Modifier" variant="info" />
                        @else
                            <x-admin.action-btn type="submit" icon="save" label="Enregistrer" variant="success" />
                        @endif
                    </div>
                </div>
            </form>

            <div class="admin-form-shell max-w-full mt-3">
                <div class="admin-product-form-toolbar justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="charge_month" class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Mois</label>
                            <input
                                type="month"
                                id="charge_month"
                                name="charge_month"
                                value="{{ $chargeMonth }}"
                                class="form-input text-sm py-1.5 w-full sm:w-[180px]"
                            >
                        </div>
                        <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap">Rechercher</button>
                    </form>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-admin.action-btn
                            type="button"
                            icon="edit"
                            label="Modifier"
                            variant="info"
                            x-bind:disabled="!selectedId"
                            @click="selectedId && openExpense(selectedId)"
                        />
                        <form id="charge-delete-form" method="POST" :action="selectedId ? '{{ url('charges/expenses') }}/' + selectedId : '#'" class="inline">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="charge_month" value="{{ $chargeMonth }}">
                            <x-admin.action-btn
                                type="button"
                                icon="delete"
                                label="Supprimer"
                                variant="danger"
                                x-bind:disabled="!selectedId"
                                @click="if (selectedId && confirm('Supprimer cette charge ?')) { $el.closest('form').submit(); }"
                            />
                        </form>
                        <x-admin.action-btn
                            type="button"
                            icon="print"
                            label="Imprimer"
                            variant="muted"
                            @click="window.open('{{ $printUrl }}', '_blank')"
                        />
                        <x-admin.action-btn
                            type="button"
                            icon="print"
                            label="Exporter PDF"
                            variant="default"
                            @click="window.location.href='{{ $exportPdfUrl }}'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 min-h-0 pt-3 overflow-hidden">
            <x-admin.data-table class="h-full min-h-0 rounded-xl" compact min-width="1100px">
                <x-slot:header>
                    Historique des charges —
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $chargeMonth)->locale('fr')->translatedFormat('F Y') }}
                    ({{ \Carbon\Carbon::parse($chargeDateFrom)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($chargeDateTo)->format('d/m/Y') }})
                </x-slot:header>
                @if($expensesList->hasPages())
                    <x-slot:footer>{{ $expensesList->links() }}</x-slot:footer>
                @endif
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type charge</th>
                        <th>Désignation</th>
                        <th class="text-right">Montant</th>
                        <th>Trésorerie</th>
                        <th>N°</th>
                        <th>Bnq</th>
                        <th>Tiré</th>
                        <th>Date paiement</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($expensesList as $expense)
                        <tr
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer"
                            :class="selectedId === {{ $expense->id }} ? 'bg-brand-50 dark:bg-brand-900/20' : ''"
                            @click="selectedId = {{ $expense->id }}"
                        >
                            <td class="admin-table-cell whitespace-nowrap">{{ $expense->expense_date->format('d/m/Y') }}</td>
                            <td class="admin-table-cell">{{ $expense->charge_type?->label() ?? ($expense->category ?? '—') }}</td>
                            <td class="admin-table-cell font-medium">{{ $expense->title }}</td>
                            <td class="admin-table-cell text-right tabular-nums font-semibold text-rose-600 dark:text-rose-400">{{ number_format($expense->amount, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell">
                                <span @class([
                                    'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                    $expense->isCaissePayment()
                                        ? 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                        : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
                                ])>
                                    {{ $expense->treasury_mode?->label() ?? 'Caisse' }}
                                </span>
                            </td>
                            <td class="admin-table-cell font-mono text-xs {{ $expense->isCaissePayment() ? 'text-slate-400' : '' }}">{{ $expense->payment_number ?? '—' }}</td>
                            <td class="admin-table-cell {{ $expense->isCaissePayment() ? 'text-slate-400' : '' }}">{{ $expense->bank?->label() ?? '—' }}</td>
                            <td class="admin-table-cell {{ $expense->isCaissePayment() ? 'text-slate-400' : '' }}">{{ $expense->drawer_name ?? '—' }}</td>
                            <td class="admin-table-cell whitespace-nowrap {{ $expense->isCaissePayment() ? 'text-slate-400' : '' }}">{{ $expense->instrument_date?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucune charge sur cette période</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </div>
</x-admin-layout>
