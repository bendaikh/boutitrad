<x-admin-layout title="Paie Commerciaux" full-height>
    @php
        $formPayroll = $editingPayroll ?? null;
        $isEdit = (bool) $formPayroll;
        $formActive = ($formActive ?? false) || $errors->any();
        $payrollFilters = $payrollFilters ?? [];
        $routeParams = fn (array $extra = []) => array_merge($payrollFilters, $extra);
        $nouveauUrl = route('sales.payments', $routeParams(['new' => 1]));
        $annulerUrl = route('sales.payments', $payrollFilters);
        $editBaseUrl = route('sales.payments', $payrollFilters);
        $printUrl = route('sales.payments.print', $payrollFilters);
        $exportPdfUrl = route('sales.payments.export.pdf', $payrollFilters);
        $initialSelectedId = ($selectedPayrollId ?? null) ?? $formPayroll?->id;
    @endphp

    <div
        x-data="payrollForm({
            selectedId: {{ $initialSelectedId ?? 'null' }},
            formActive: {{ $formActive ? 'true' : 'false' }},
            isEdit: {{ $isEdit ? 'true' : 'false' }},
            payMonth: @js(old('pay_month', $formPayroll?->pay_month ?? now()->format('Y-m'))),
            commercialId: @js(old('commercial_id', $formPayroll?->commercial_id ?? '')),
            paymentDate: @js(old('payment_date', $formPayroll?->payment_date?->format('Y-m-d') ?? date('Y-m-d'))),
            reference: @js($formPayroll?->reference ?? $previewReference),
            previewReference: @js($previewReference),
            payrollId: {{ $formPayroll?->id ?? 'null' }},
            statsUrl: @js(route('sales.payments.stats')),
            salesCount: {{ (int) old('sales_count', $formPayroll?->sales_count ?? 0) }},
            revenue: {{ (float) old('revenue', $formPayroll?->revenue ?? 0) }},
            commissionAmount: {{ (float) old('commission_amount', $formPayroll?->commission_amount ?? 0) }},
            amountToPay: {{ (float) old('amount_to_pay', $formPayroll?->amount_to_pay ?? 0) }},
            commissionRate: {{ (float) old('commission_rate', $formPayroll?->commission_rate ?? 0) }},
            loadingStats: false,
            duplicateWarning: false,
            payrollFilters: @js($payrollFilters),
            paymentsIndexUrl: @js(route('sales.payments')),
        })"
        x-init="init()"
        class="flex flex-col flex-1 min-h-0 w-full"
    >
        <div class="shrink-0 space-y-3 pb-3 border-b border-slate-200/80 dark:border-slate-800 bg-surface-muted dark:bg-slate-950">
            <form
                id="payroll-form"
                method="POST"
                action="{{ $isEdit && $formPayroll ? route('sales.payments.update', $formPayroll) : route('sales.payments.store') }}"
                class="admin-form-shell max-w-full"
                x-show="formActive"
                x-cloak
            >
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="px-3 py-2.5 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-brand-50/80 to-white dark:from-brand-900/25 dark:to-slate-900 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="isEdit ? 'Modifier la paie commerciale' : 'Saisie paie commerciaux'"></h2>
                    <a href="{{ $annulerUrl }}" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 shrink-0">Annuler</a>
                </div>

                <div class="admin-order-form-bar">
                    <div class="admin-payroll-form-grid">
                        <div class="admin-order-form-field">
                            <label for="payment_date" class="admin-order-form-label text-center">Date</label>
                            <input type="date" id="payment_date" name="payment_date" x-model="paymentDate" required class="admin-order-form-input text-center">
                            @error('payment_date')<p class="text-red-500 text-[10px] mt-0.5 text-center">{{ $message }}</p>@enderror
                            <p class="admin-order-form-hint text-center">&nbsp;</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label class="admin-order-form-label text-center">Réf Paie</label>
                            <input type="text" readonly x-model="reference" class="admin-order-form-readonly font-mono text-xs text-center">
                            <p class="admin-order-form-hint text-center">Générée automatiquement</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label for="pay_month" class="admin-order-form-label text-center">Mois de Paie</label>
                            <input type="month" id="pay_month" name="pay_month" x-model="payMonth" @change="fetchStats()" required class="admin-order-form-input text-center">
                            @error('pay_month')<p class="text-red-500 text-[10px] mt-0.5 text-center">{{ $message }}</p>@enderror
                            <p class="admin-order-form-hint text-center">Période des ventes confirmées</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label for="commercial_id" class="admin-order-form-label text-center">Nom Commercial</label>
                            <select id="commercial_id" name="commercial_id" x-model="commercialId" @change="fetchStats()" required class="admin-order-form-input text-center">
                                <option value="">— Choisir —</option>
                                @foreach($commercials as $commercial)
                                    <option value="{{ $commercial->id }}" @selected(old('commercial_id', $formPayroll?->commercial_id ?? '') == $commercial->id)>{{ $commercial->name }}</option>
                                @endforeach
                            </select>
                            @error('commercial_id')<p class="text-red-500 text-[10px] mt-0.5 text-center">{{ $message }}</p>@enderror
                            <p class="admin-order-form-hint text-center">&nbsp;</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label class="admin-order-form-label text-center">Nbrs Ventes</label>
                            <input type="text" readonly :value="formatInteger(salesCount)" class="admin-order-form-readonly tabular-nums text-center font-medium">
                            <p class="admin-order-form-hint text-center">Ventes confirmées du mois</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label class="admin-order-form-label text-center">Mnt règlement</label>
                            <input type="text" readonly :value="formatMoney(revenue)" class="admin-order-form-readonly tabular-nums text-center font-medium text-emerald-700 dark:text-emerald-400">
                            <p class="admin-order-form-hint text-center">Chiffre réalisé</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label class="admin-order-form-label text-center">
                                Commission
                                <span x-show="commissionRate > 0" x-cloak class="normal-case font-normal text-slate-400" x-text="'(' + formatPercent(commissionRate) + ')'"></span>
                            </label>
                            <input type="text" readonly :value="formatMoney(commissionAmount)" class="admin-order-form-readonly tabular-nums text-center">
                            <p class="admin-order-form-hint text-center">Taux appliqué sur le chiffre</p>
                        </div>
                        <div class="admin-order-form-field">
                            <label class="admin-order-form-label text-center">Montant à Payer</label>
                            <input type="text" readonly :value="formatMoney(amountToPay)" class="admin-order-form-readonly tabular-nums text-center font-semibold text-brand-800 dark:text-brand-300">
                            <p class="admin-order-form-hint text-center" x-show="duplicateWarning" x-cloak>Paie déjà existante pour ce mois</p>
                            <p class="admin-order-form-hint text-center" x-show="!duplicateWarning">&nbsp;</p>
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

            <div class="admin-form-shell max-w-full">
                <div class="admin-product-form-toolbar justify-end">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-admin.action-btn
                            type="button"
                            icon="plus"
                            label="Nouveau"
                            variant="success"
                            @click="window.location.href='{{ $nouveauUrl }}'"
                        />
                        <x-admin.action-btn
                            type="button"
                            icon="edit"
                            label="Modifier"
                            variant="info"
                            x-bind:disabled="!selectedId"
                            @click="selectedId && openPayroll(selectedId)"
                        />
                        <form id="payroll-delete-form" method="POST" :action="selectedId ? '{{ url('sales/payments') }}/' + selectedId : '#'" class="inline">
                            @csrf
                            @method('DELETE')
                            <x-admin.action-btn
                                type="button"
                                icon="delete"
                                label="Supprimer"
                                variant="danger"
                                x-bind:disabled="!selectedId"
                                @click="if (selectedId && confirm('Supprimer cette paie commerciale ?')) { $el.closest('form').submit(); }"
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
            <x-admin.data-table class="payroll-table h-full min-h-0 rounded-xl" compact min-width="1100px">
                <x-slot:header>Historique des paies</x-slot:header>
                @if($payrolls->hasPages())
                    <x-slot:footer>{{ $payrolls->links() }}</x-slot:footer>
                @endif
                <colgroup>
                    <col style="width: 9%">
                    <col style="width: 11%">
                    <col style="width: 11%">
                    <col style="width: 18%">
                    <col style="width: 8%">
                    <col style="width: 14%">
                    <col style="width: 14%">
                    <col style="width: 15%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Réf Paie</th>
                        <th>Mois Payé</th>
                        <th>Nom Commercial</th>
                        <th>Nbrs Ventes</th>
                        <th>Chiffre Réalisé</th>
                        <th>Commission</th>
                        <th>Montant à Payer</th>
                    </tr>
                    <tr class="admin-th-filter-row bg-slate-600 dark:bg-slate-800">
                        <th>
                            <x-sales.payroll-th-filter field="pf_date" :filters="$payrollFilters" class="admin-th-filter--medium">
                                <input type="date" name="pf_date" value="{{ request('pf_date') }}" onchange="this.form.submit()" aria-label="Filtrer par date">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_reference" :filters="$payrollFilters" class="admin-th-filter--ref">
                                <input type="text" name="pf_reference" value="{{ request('pf_reference') }}" placeholder="Réf…" onchange="this.form.submit()" aria-label="Filtrer par référence">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_pay_month" :filters="$payrollFilters" class="admin-th-filter--medium">
                                <input type="month" name="pf_pay_month" value="{{ request('pf_pay_month') }}" onchange="this.form.submit()" aria-label="Filtrer par mois payé">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_commercial_id" :filters="$payrollFilters" class="admin-th-filter--wide">
                                <select name="pf_commercial_id" onchange="this.form.submit()" aria-label="Filtrer par commercial">
                                    <option value="">Tous</option>
                                    @foreach($commercials as $commercial)
                                        <option value="{{ $commercial->id }}" @selected((string) request('pf_commercial_id') === (string) $commercial->id)>{{ $commercial->name }}</option>
                                    @endforeach
                                </select>
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_sales_count" :filters="$payrollFilters" class="admin-th-filter--narrow">
                                <input type="number" min="0" step="1" name="pf_sales_count" value="{{ request('pf_sales_count') }}" placeholder="Nbr" onchange="this.form.submit()" aria-label="Filtrer par nombre de ventes">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_revenue" :filters="$payrollFilters" class="admin-th-filter--medium">
                                <input type="text" inputmode="decimal" name="pf_revenue" value="{{ request('pf_revenue') }}" placeholder="Min DH" onchange="this.form.submit()" aria-label="Filtrer par chiffre réalisé">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_commission" :filters="$payrollFilters" class="admin-th-filter--medium">
                                <input type="text" inputmode="decimal" name="pf_commission" value="{{ request('pf_commission') }}" placeholder="Min DH" onchange="this.form.submit()" aria-label="Filtrer par commission">
                            </x-sales.payroll-th-filter>
                        </th>
                        <th>
                            <x-sales.payroll-th-filter field="pf_amount" :filters="$payrollFilters" class="admin-th-filter--medium">
                                <input type="text" inputmode="decimal" name="pf_amount" value="{{ request('pf_amount') }}" placeholder="Min DH" onchange="this.form.submit()" aria-label="Filtrer par montant à payer">
                            </x-sales.payroll-th-filter>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($payrolls as $payroll)
                        <tr
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer"
                            :class="selectedId === {{ $payroll->id }} ? 'bg-brand-50 dark:bg-brand-900/20' : ''"
                            @click="selectedId = {{ $payroll->id }}"
                        >
                            <td class="admin-table-cell">{{ $payroll->payment_date->format('d/m/Y') }}</td>
                            <td class="admin-table-cell font-mono text-xs">{{ $payroll->reference }}</td>
                            <td class="admin-table-cell">{{ $payroll->payMonthLabel() }}</td>
                            <td class="admin-table-cell font-medium">{{ $payroll->commercial?->name ?? '—' }}</td>
                            <td class="admin-table-cell tabular-nums">{{ number_format($payroll->sales_count, 0, ',', ' ') }}</td>
                            <td class="admin-table-cell tabular-nums">{{ number_format($payroll->revenue, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell tabular-nums">{{ number_format($payroll->commission_amount, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell tabular-nums font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($payroll->amount_to_pay, 2, ',', ' ') }} DH</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucune paie commerciale enregistrée
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </div>

    @push('scripts')
    <script>
        function payrollForm(config) {
            return {
                ...config,
                init() {
                    if (this.formActive) {
                        this.fetchStats();
                    }
                },
                async fetchStats() {
                    if (! this.commercialId || ! this.payMonth) {
                        this.resetStats();
                        return;
                    }

                    this.loadingStats = true;

                    try {
                        const params = new URLSearchParams({
                            commercial_id: this.commercialId,
                            pay_month: this.payMonth,
                        });

                        if (this.payrollId) {
                            params.set('exclude_id', this.payrollId);
                        }

                        const response = await fetch(`${this.statsUrl}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                        });

                        if (! response.ok) {
                            throw new Error('stats unavailable');
                        }

                        const data = await response.json();
                        this.salesCount = data.sales_count ?? 0;
                        this.revenue = data.revenue ?? 0;
                        this.commissionRate = data.commission_rate ?? 0;
                        this.commissionAmount = data.commission_amount ?? 0;
                        this.amountToPay = data.amount_to_pay ?? 0;
                        this.duplicateWarning = !! data.duplicate;
                    } catch (error) {
                        this.resetStats();
                    } finally {
                        this.loadingStats = false;
                    }
                },
                resetStats() {
                    this.salesCount = 0;
                    this.revenue = 0;
                    this.commissionRate = 0;
                    this.commissionAmount = 0;
                    this.amountToPay = 0;
                    this.duplicateWarning = false;
                },
                formatMoney(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value ?? 0) + ' DH';
                },
                formatInteger(value) {
                    return new Intl.NumberFormat('fr-FR').format(value ?? 0);
                },
                formatPercent(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value ?? 0) + ' %';
                },
                openPayroll(id) {
                    const params = new URLSearchParams({ ...this.payrollFilters, selected: id });
                    window.location.href = `${this.paymentsIndexUrl}?${params.toString()}`;
                },
            };
        }
    </script>
    @endpush
</x-admin-layout>
