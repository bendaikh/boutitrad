<x-admin-layout title="Commerciaux">
    @php
        $formCommercial = $editingCommercial ?? null;
        $isEdit = (bool) $formCommercial;
        $formActive = $formActive || $errors->any();
        $nouveauUrl = route('commercials.index', ['new' => 1]);
        $annulerUrl = route('commercials.index');
        $editBaseUrl = route('commercials.index');
    @endphp

    <div
        x-data="{
            formActive: {{ $formActive ? 'true' : 'false' }},
            isEdit: {{ $isEdit ? 'true' : 'false' }},
            selectedId: {{ $formCommercial?->id ?? 'null' }},
            deleteAction: '',
        }"
        x-init="$watch('selectedId', id => { deleteAction = id ? '{{ url('commercials') }}/' + id : '' })"
    >
        <x-admin.list-page>
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
                            Fiche commercial — consultation
                        @elseif($isEdit)
                            Modifier — {{ $formCommercial->formattedCommercialId() }} · {{ $formCommercial->name }}
                        @else
                            Nouveau commercial
                        @endif
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                        @if(! $formActive)
                            Cliquez sur « Ajouter » pour activer la saisie
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

            <x-admin.data-table min-width="1200px" class="flex-1 min-h-0">
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <span>Liste des commerciaux</span>
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
                    </div>
                </x-slot:header>
                <thead>
                    <tr>
                        <th class="text-left">ID commercial</th>
                        <th class="text-left">Nom commercial</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Email</th>
                        <th class="text-left">WhatsApp</th>
                        <th class="text-left">Zone prospect</th>
                        <th class="text-right">Commission (%)</th>
                        <th class="text-right">CA vendu</th>
                        <th class="text-center">Cmd. livrées</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse($commercials as $commercial)
                        <tr
                            class="admin-row-hover cursor-pointer"
                            :class="selectedId === {{ $commercial->id }} ? 'admin-row-selected' : ''"
                            @click="selectedId = {{ $commercial->id }}"
                            @dblclick="window.location.href = '{{ $editBaseUrl }}?edit={{ $commercial->id }}'"
                        >
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $commercial->formattedCommercialId() }}</td>
                            <td class="admin-table-cell font-medium">
                                <a href="{{ route('commercials.show', $commercial) }}" class="text-brand-600 hover:underline" @click.stop>
                                    {{ $commercial->name }}
                                </a>
                            </td>
                            <td class="admin-table-cell">{{ $commercial->phone ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $commercial->email }}</td>
                            <td class="admin-table-cell">{{ $commercial->whatsapp ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $commercial->prospect_zone ?? '—' }}</td>
                            <td class="admin-table-cell text-right tabular-nums">
                                {{ $commercial->commission_rate !== null ? number_format($commercial->commission_rate, 1, ',', ' ') . ' %' : '—' }}
                            </td>
                            <td class="admin-table-cell text-right tabular-nums">{{ number_format($commercial->total_sales ?? 0, 0, ',', ' ') }} DH</td>
                            <td class="admin-table-cell text-center tabular-nums">{{ $commercial->delivered_orders_count ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-table-cell text-center text-slate-500 dark:text-slate-400 py-8">
                                Aucun commercial. Cliquez sur « Ajouter » pour en créer un.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
