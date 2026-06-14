<x-admin-layout title="Paiement">
    <div x-data="paymentForm()" x-init="init()">
        <x-admin.list-page>
            <form
                method="POST"
                action="{{ route('sales.payments.store') }}"
                class="admin-form-shell max-w-full shrink-0"
            >
                @csrf

                <div class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
                    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Saisie paiement</h2>
                </div>

                <div class="admin-order-form-bar">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-2 gap-y-2">
                        <div>
                            <label for="payment_date" class="admin-order-form-label">Date</label>
                            <input
                                type="date"
                                id="payment_date"
                                name="payment_date"
                                value="{{ old('payment_date', date('Y-m-d')) }}"
                                required
                                class="admin-order-form-input"
                            >
                            @error('payment_date')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="order_id" class="admin-order-form-label">N° de bon à régler</label>
                            <select
                                id="order_id"
                                name="order_id"
                                x-model="orderId"
                                required
                                class="admin-order-form-input"
                            >
                                <option value="">— Choisir un bon impayé —</option>
                                @foreach($unpaidOrders as $order)
                                    <option
                                        value="{{ $order->id }}"
                                        data-client="{{ $order->client->name }}"
                                        data-balance="{{ $order->balanceDue() }}"
                                        data-payment-mode="{{ \App\Enums\PaymentMode::forPaymentForm($order->payment_mode)?->value ?? '' }}"
                                        @selected(old('order_id') == $order->id)
                                    >
                                        {{ $order->reference }} — {{ number_format($order->balanceDue(), 2, ',', ' ') }} DH
                                    </option>
                                @endforeach
                            </select>
                            @error('order_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="admin-order-form-label">Nom client</label>
                            <input
                                type="text"
                                x-model="clientName"
                                readonly
                                class="admin-order-form-readonly"
                                placeholder="Sélectionnez un bon"
                            >
                        </div>
                    </div>
                </div>

                <div class="admin-order-form-bar">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-x-2 gap-y-2">
                        <div>
                            <label for="payment_mode" class="admin-order-form-label">Type paiement</label>
                            <select
                                id="payment_mode"
                                name="payment_mode"
                                x-model="paymentMode"
                                required
                                class="admin-order-form-input"
                            >
                                <option value="">— Mode —</option>
                                @foreach($paymentModes as $mode)
                                    <option value="{{ $mode->value }}" @selected(old('payment_mode') === $mode->value)>
                                        {{ $mode->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_mode')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bank" class="admin-order-form-label">Banque</label>
                            <select id="bank" name="bank" class="admin-order-form-input">
                                <option value="">— Choisir —</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->value }}" @selected(old('bank') === $bank->value)>
                                        {{ $bank->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="payment_number" class="admin-order-form-label">N° de règlement</label>
                            <input
                                type="text"
                                id="payment_number"
                                name="payment_number"
                                value="{{ old('payment_number') }}"
                                class="admin-order-form-input"
                            >
                            @error('payment_number')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="settlement_status" class="admin-order-form-label">Statut règl.</label>
                            <select id="settlement_status" name="settlement_status" class="admin-order-form-input">
                                <option value="">— Choisir —</option>
                                @foreach($settlementStatuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('settlement_status') === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('settlement_status')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="drawer_name" class="admin-order-form-label">Nom tiré</label>
                            <input
                                type="text"
                                id="drawer_name"
                                name="drawer_name"
                                value="{{ old('drawer_name') }}"
                                class="admin-order-form-input"
                            >
                            @error('drawer_name')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="admin-order-form-bar">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-x-2 gap-y-2 items-end">
                        <div>
                            <label for="encashment_date" class="admin-order-form-label">Date encaiss.</label>
                            <input
                                type="date"
                                id="encashment_date"
                                name="encashment_date"
                                value="{{ old('encashment_date') }}"
                                class="admin-order-form-input"
                            >
                            @error('encashment_date')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="regulation_status" class="admin-order-form-label">Statut règlement</label>
                            <select id="regulation_status" name="regulation_status" required class="admin-order-form-input">
                                <option value="">— Choisir —</option>
                                @foreach($regulationStatuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('regulation_status') === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('regulation_status')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="treasury_id" class="admin-order-form-label">Trésorerie</label>
                            <select id="treasury_id" name="treasury_id" class="admin-order-form-input">
                                <option value="">— Choisir —</option>
                                @foreach($treasuries as $treasury)
                                    <option value="{{ $treasury->id }}" @selected(old('treasury_id') == $treasury->id)>
                                        {{ $treasury->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('treasury_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="amount" class="admin-order-form-label">Mnt règlement</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                id="amount"
                                name="amount"
                                x-model="amount"
                                required
                                class="admin-order-form-input"
                                placeholder="0,00"
                            >
                            @error('amount')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex justify-end">
                            <x-admin.action-btn type="submit" icon="save" label="Enregistrer" variant="success" class="w-full sm:w-auto" />
                        </div>
                    </div>
                </div>
            </form>

            <x-admin.data-table class="flex-1 min-h-0 mt-3">
                @if($payments->hasPages())
                    <x-slot:footer>{{ $payments->links() }}</x-slot:footer>
                @endif
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th class="text-left">N° bon</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Type règlement</th>
                        <th class="text-left">Banque</th>
                        <th class="text-right">Mnt règlement</th>
                        <th class="text-left">Date encaiss.</th>
                        <th class="text-left">Trésorerie</th>
                        <th class="text-left">
                            <span>Statut</span>
                            <span class="block text-[10px] font-normal text-slate-400 dark:text-slate-500">Cliquer pour modifier</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($payments as $payment)
                        @php $status = $payment->regulationStatus(); @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell">{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="admin-table-cell font-mono text-xs">{{ $payment->order->reference }}</td>
                            <td class="admin-table-cell">{{ $payment->order->client->name }}</td>
                            <td class="admin-table-cell">{{ $payment->payment_mode->label() }}</td>
                            <td class="admin-table-cell">{{ $payment->bank ?? '—' }}</td>
                            <td class="admin-table-cell text-right font-medium text-emerald-600 dark:text-emerald-400">
                                {{ number_format($payment->amount, 2, ',', ' ') }} DH
                            </td>
                            <td class="admin-table-cell">
                                {{ $payment->encashment_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="admin-table-cell">{{ $payment->treasury?->name ?? '—' }}</td>
                            <td class="admin-table-cell">
                                <form
                                    method="POST"
                                    action="{{ route('sales.payments.update-status', $payment) }}"
                                    class="inline-block min-w-[9rem]"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <select
                                        name="regulation_status"
                                        title="Choisir le statut du règlement"
                                        onchange="this.form.submit()"
                                        class="w-full text-xs font-medium rounded-full px-2.5 py-1 border-0 cursor-pointer focus:ring-2 focus:ring-brand-500 {{ $status->badgeClass() }}"
                                    >
                                        @foreach($regulationStatuses as $option)
                                            <option value="{{ $option->value }}" @selected($status->value === $option->value)>
                                                {{ $option->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                                Aucun paiement enregistré
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>

    @push('scripts')
    <script>
        function paymentForm() {
            return {
                orderId: @json(old('order_id', '')),
                clientName: '',
                amount: @json(old('amount', '')),
                paymentMode: @json(old('payment_mode', '')),
                init() {
                    this.$watch('orderId', () => this.syncOrder());
                    this.syncOrder();
                },
                syncOrder() {
                    const select = document.getElementById('order_id');
                    const option = select?.selectedOptions?.[0];

                    if (! option?.value) {
                        this.clientName = '';
                        return;
                    }

                    this.clientName = option.dataset.client || '';
                    this.amount = option.dataset.balance || '';

                    if (option.dataset.paymentMode) {
                        this.paymentMode = option.dataset.paymentMode;
                    }
                },
            };
        }
    </script>
    @endpush
</x-admin-layout>
