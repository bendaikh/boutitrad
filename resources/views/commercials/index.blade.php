<x-admin-layout title="{{ ($commercialView ?? false) ? 'Mon activité commerciale' : 'Commerciaux' }}">
    @php
        $formCommercial = $editingCommercial ?? null;
        $isEdit = (bool) $formCommercial;
        $formActive = ($formActive ?? false) || $errors->any();
        $nouveauUrl = route('commercials.index', ['new' => 1]);
        $annulerUrl = route('commercials.index');
        $editBaseUrl = route('commercials.index');
        $isCommercialView = $commercialView ?? false;
        $initialSelectedId = $formCommercial?->id;
    @endphp

    <div
        @unless($isCommercialView)
        x-data="{
            formActive: {{ $formActive ? 'true' : 'false' }},
            isEdit: {{ $isEdit ? 'true' : 'false' }},
            selectedId: {{ $initialSelectedId ?? 'null' }},
            pickerOpen: false,
            deleteAction: '',
            commercials: @js($commercialsJson ?? []),
            defaultCommissionRate: {{ $defaultCommissionRate ?? 5 }},
            get selected() {
                if (! this.selectedId) {
                    return null;
                }

                return this.commercials.find(c => c.id === this.selectedId) ?? null;
            },
            pickCommercial(id) {
                this.selectedId = id;
            },
            formatMoney(value) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value ?? 0) + ' DH';
            },
            formatPercent(value) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value ?? 0) + ' %';
            },
        }"
        x-init="$watch('selectedId', id => { deleteAction = id ? '{{ url('commercials') }}/' + id : '' })"
        @endunless
    >
        <x-admin.list-page>
            @unless($isCommercialView)
            <form
                id="commercial-form"
                method="POST"
                action="{{ $isEdit ? route('commercials.update', $formCommercial) : route('commercials.store') }}"
                class="admin-form-shell max-w-full shrink-0"
            >
                @csrf
                @if($isEdit) @method('PUT') @endif

                <div class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 {{ $formActive ? 'bg-slate-50 dark:bg-slate-800/60' : 'bg-slate-100 dark:bg-slate-800/80' }}">
                    <h2 class="text-sm font-semibold {{ $formActive ? 'text-slate-800 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}">
                        @if(! $formActive)
                            <span x-show="!selected">Fiche commercial — sélectionnez un commercial dans le tableau</span>
                            <span x-show="selected" x-cloak x-text="'Fiche commercial — ' + (selected?.formatted_id ?? '') + ' · ' + (selected?.name ?? '')"></span>
                        @elseif($isEdit)
                            Modifier — {{ $formCommercial->formattedCommercialId() }} · {{ $formCommercial->name }}
                        @else
                            Nouveau commercial
                        @endif
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                        @if(! $formActive)
                            Utilisez la flèche sur « Nom commercial » pour consulter
                        @elseif($isEdit)
                            Modifiez puis cliquez sur « Modifier » pour enregistrer
                        @else
                            Remplissez le formulaire puis cliquez sur « Ajouter »
                        @endif
                    </span>
                </div>

                <div class="relative transition-opacity" :class="!formActive && 'opacity-55'">
                    <div
                        x-show="!formActive"
                        x-cloak
                        class="absolute inset-0 z-10 bg-slate-200/25 dark:bg-slate-900/20 cursor-not-allowed"
                    ></div>

                    <fieldset x-bind:disabled="!formActive" class="border-0 p-0 m-0 min-w-0">
                        <div class="admin-order-form-bar">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-2 gap-y-2">
                                <div>
                                    <label class="admin-order-form-label">ID commercial</label>
                                    <input
                                        type="text"
                                        value="{{ $isEdit ? $formCommercial->formattedCommercialId() : $previewCommercialId }}"
                                        x-bind:value="formActive ? undefined : (selected?.formatted_id ?? '')"
                                        readonly
                                        class="admin-order-form-readonly font-mono text-xs"
                                    >
                                </div>
                                <div>
                                    <label for="commercial_name" class="admin-order-form-label">Nom commercial *</label>
                                    <input
                                        type="text"
                                        id="commercial_name"
                                        name="name"
                                        value="{{ old('name', $formCommercial->name ?? '') }}"
                                        x-bind:value="formActive ? undefined : (selected?.name ?? '')"
                                        required
                                        class="admin-order-form-input"
                                    >
                                    @error('name')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="commercial_phone" class="admin-order-form-label">Contact</label>
                                    <input
                                        type="text"
                                        id="commercial_phone"
                                        name="phone"
                                        value="{{ old('phone', $formCommercial->phone ?? '') }}"
                                        x-bind:value="formActive ? undefined : (selected?.phone ?? '')"
                                        placeholder="06..."
                                        class="admin-order-form-input"
                                    >
                                    @error('phone')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="commercial_email" class="admin-order-form-label">Email *</label>
                                    <input
                                        type="email"
                                        id="commercial_email"
                                        name="email"
                                        value="{{ old('email', $formCommercial->email ?? '') }}"
                                        x-bind:value="formActive ? undefined : (selected?.email ?? '')"
                                        required
                                        class="admin-order-form-input"
                                    >
                                    @error('email')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="admin-order-form-bar">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-2 gap-y-2">
                                <div>
                                    <label for="commercial_whatsapp" class="admin-order-form-label">WhatsApp</label>
                                    <input
                                        type="text"
                                        id="commercial_whatsapp"
                                        name="whatsapp"
                                        value="{{ old('whatsapp', $formCommercial->whatsapp ?? '') }}"
                                        x-bind:value="formActive ? undefined : (selected?.whatsapp ?? '')"
                                        placeholder="06..."
                                        class="admin-order-form-input"
                                    >
                                    @error('whatsapp')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="commercial_prospect_zone" class="admin-order-form-label">Zone prospect</label>
                                    <input
                                        type="text"
                                        id="commercial_prospect_zone"
                                        name="prospect_zone"
                                        value="{{ old('prospect_zone', $formCommercial->prospect_zone ?? '') }}"
                                        x-bind:value="formActive ? undefined : (selected?.prospect_zone ?? '')"
                                        placeholder="Ex. Casablanca, Rabat..."
                                        class="admin-order-form-input"
                                    >
                                    @error('prospect_zone')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="commercial_commission_rate" class="admin-order-form-label">Commission affectée (%)</label>
                                    <input
                                        type="number"
                                        id="commercial_commission_rate"
                                        name="commission_rate"
                                        value="{{ old('commission_rate', $formCommercial->commission_rate ?? $defaultCommissionRate) }}"
                                        x-bind:value="formActive ? undefined : (selected?.commission_rate ?? defaultCommissionRate)"
                                        min="0"
                                        max="100"
                                        step="0.1"
                                        placeholder="%"
                                        class="admin-order-form-input text-right tabular-nums"
                                    >
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Appliquée sur le total des commandes livrées</p>
                                    @error('commission_rate')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="admin-product-form-actions">
                    <div class="flex flex-wrap items-center justify-end gap-2 notranslate" translate="no">
                        @if($formActive && $isEdit)
                            <x-admin.action-btn type="submit" icon="edit" label="Modifier" variant="info" />
                        @elseif($formActive)
                            <x-admin.action-btn type="submit" icon="plus" label="Ajouter" variant="success" />
                        @else
                            <x-admin.action-btn
                                icon="plus"
                                label="Ajouter"
                                variant="success"
                                @click="window.location.href = '{{ $nouveauUrl }}'"
                            />
                        @endif

                        @if(! ($formActive && $isEdit))
                            <x-admin.action-btn
                                icon="edit"
                                label="Modifier"
                                variant="info"
                                x-bind:disabled="!selectedId || (formActive && !isEdit)"
                                @click="selectedId && !formActive && (window.location.href = '{{ $editBaseUrl }}?edit=' + selectedId)"
                            />
                        @endif

                        <x-admin.action-btn
                            icon="delete"
                            label="Supprimer"
                            variant="danger-solid"
                            x-bind:disabled="!selectedId"
                            @click="if (selectedId && confirm('Supprimer ce commercial ?')) { document.getElementById('commercial-delete-form').submit(); }"
                        />

                        <x-admin.action-btn
                            icon="cancel"
                            label="Annuler"
                            variant="muted"
                            x-bind:disabled="!formActive"
                            @click="window.location.href = '{{ $annulerUrl }}'"
                        />
                    </div>
                </div>
            </form>

            <form id="commercial-delete-form" method="POST" x-bind:action="deleteAction" class="hidden">
                @csrf
                @method('DELETE')
            </form>
            @endunless

            <x-admin.data-table min-width="1200px" class="flex-1 min-h-0 commercials-table">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <span>{{ $isCommercialView ? 'Mon état commercial' : 'Liste des commerciaux' }}</span>
                        @unless($isCommercialView)
                        <div class="flex items-center gap-2 notranslate font-normal" translate="no">
                            <x-admin.action-btn
                                icon="print"
                                label="Imprimer"
                                @click="window.open('{{ route('commercials.print') }}', '_blank')"
                            />
                            <x-admin.action-btn
                                icon="save"
                                label="Exporter"
                                variant="success"
                                :href="route('commercials.export')"
                                target="_blank"
                            />
                        </div>
                        @endunless
                    </div>
                </x-slot:header>
                <thead>
                    <tr>
                        <th class="text-center align-top">ID commercial</th>
                        <th class="text-center align-top">
                            @unless($isCommercialView)
                            <div class="relative inline-flex items-center justify-center gap-1.5 mx-auto" @click.outside="pickerOpen = false">
                                <span>Nom commercial</span>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center w-6 h-6 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500 hover:text-brand-600 hover:border-brand-400 transition-colors"
                                    title="Sélectionner un commercial"
                                    @click.stop="pickerOpen = !pickerOpen"
                                >
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="pickerOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div
                                    x-show="pickerOpen"
                                    x-cloak
                                    x-transition
                                    class="absolute top-full left-0 z-50 mt-1 min-w-[16rem] max-w-[20rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden"
                                >
                                    <div class="px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                        Choisir un commercial
                                    </div>
                                    <div class="max-h-56 overflow-y-auto py-1">
                                        <template x-for="commercial in commercials" :key="commercial.id">
                                            <button
                                                type="button"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2"
                                                :class="selectedId === commercial.id ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300 font-medium' : 'text-slate-700 dark:text-slate-200'"
                                                @click="pickCommercial(commercial.id); pickerOpen = false"
                                            >
                                                <span class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="commercial.formatted_id"></span>
                                                <span x-text="commercial.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @else
                                Nom commercial
                            @endunless
                        </th>
                        @unless($isCommercialView)
                        <th class="text-center align-top">Contact</th>
                        <th class="text-center align-top">Email</th>
                        <th class="text-center align-top">WhatsApp</th>
                        <th class="text-center align-top">Zone prospect</th>
                        @endunless
                        <th class="text-center align-top">Commission affectée</th>
                        <th class="text-center align-top">Cmd. livrées</th>
                        <th class="text-center align-top">CA vendu</th>
                        <th class="text-center align-top">Total commissions</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @if($isCommercialView)
                        @forelse($commercials as $commercial)
                            <tr>
                                <td class="admin-table-cell-muted font-mono text-xs text-center align-top">{{ $commercial->formattedCommercialId() }}</td>
                                <td class="admin-table-cell font-medium text-center align-top">{{ $commercial->name }}</td>
                                <td class="admin-table-cell text-center align-top tabular-nums">
                                    {{ number_format($commercial->effective_commission_rate ?? 0, 1, ',', ' ') }} %
                                </td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-semibold">{{ $commercial->delivered_orders_count ?? 0 }}</td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-medium">{{ number_format($commercial->total_sales ?? 0, 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ number_format($commercial->total_commissions ?? 0, 2, ',', ' ') }} DH
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-table-cell text-center text-slate-500 dark:text-slate-400 py-8">
                                    Aucune donnée disponible pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr x-show="!selectedId" x-cloak>
                            <td colspan="10" class="admin-table-cell text-center align-top text-slate-500 dark:text-slate-400 py-10">
                                Cliquez sur la flèche à côté de « Nom commercial » pour sélectionner un commercial.
                            </td>
                        </tr>
                        <template x-for="commercial in commercials" :key="commercial.id">
                            <tr
                                x-show="selectedId === commercial.id"
                                x-cloak
                                class="admin-row-hover admin-row-selected"
                                @dblclick="window.location.href = '{{ $editBaseUrl }}?edit=' + commercial.id"
                            >
                                <td class="admin-table-cell-muted font-mono text-xs text-center align-top" x-text="commercial.formatted_id"></td>
                                <td class="admin-table-cell font-medium text-center align-top" x-text="commercial.name"></td>
                                <td class="admin-table-cell text-center align-top" x-text="commercial.phone || '—'"></td>
                                <td class="admin-table-cell text-center align-top" x-text="commercial.email"></td>
                                <td class="admin-table-cell text-center align-top" x-text="commercial.whatsapp || '—'"></td>
                                <td class="admin-table-cell text-center align-top" x-text="commercial.prospect_zone || '—'"></td>
                                <td class="admin-table-cell text-center align-top tabular-nums" x-text="formatPercent(commercial.effective_commission_rate)"></td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-semibold" x-text="commercial.delivered_orders_count"></td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-medium" x-text="formatMoney(commercial.total_sales)"></td>
                                <td class="admin-table-cell text-center align-top tabular-nums font-semibold text-emerald-700 dark:text-emerald-400" x-text="formatMoney(commercial.total_commissions)"></td>
                            </tr>
                        </template>
                        <tr x-show="commercials.length === 0">
                            <td colspan="10" class="admin-table-cell text-center text-slate-500 dark:text-slate-400 py-8">
                                Aucun commercial. Cliquez sur « Ajouter » pour en créer un.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
