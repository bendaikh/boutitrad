<x-admin-layout title="{{ ($commercialView ?? false) ? 'Mon activité commerciale' : 'Commerciaux' }}">
    @php
        $formCommercial = $editingCommercial ?? $viewingCommercial ?? null;
        $isEdit = (bool) $editingCommercial;
        $isView = (bool) ($isViewMode ?? false);
        $formActive = ($formActive ?? false) || $errors->any();
        $nouveauUrl = route('commercials.index', ['new' => 1]);
        $annulerUrl = route('commercials.index');
        $editBaseUrl = route('commercials.index');
        $viewBaseUrl = route('commercials.index');
        $isCommercialView = $commercialView ?? false;
        $initialSelectedId = ($selectedCommercialId ?? null) ?? $formCommercial?->id;
        $commercialIsActive = (bool) old('is_active', $formCommercial?->is_active ?? true);
        $commercialEmailLocal = old('email_local');

        if ($commercialEmailLocal === null) {
            $commercialEmailLocal = \App\Support\CommercialEmail::localPart(old('email', $formCommercial?->email));
        }
    @endphp

    <div
        @unless($isCommercialView)
        x-data="{
            formActive: {{ $formActive ? 'true' : 'false' }},
            isEdit: {{ $isEdit ? 'true' : 'false' }},
            isView: {{ $isView ? 'true' : 'false' }},
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
            openCommercialView(id) {
                if (this.isView && this.selectedId === id) {
                    return;
                }

                window.location.href = '{{ $viewBaseUrl }}?view=' + id;
            },
            openCommercialEdit(id) {
                window.location.href = '{{ $editBaseUrl }}?edit=' + id;
            },
            formatMoney(value) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value ?? 0) + ' DH';
            },
            formatPercent(value) {
                return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(value ?? 0) + ' %';
            },
            emailLocalFromFull(email) {
                if (! email) {
                    return '';
                }

                const domain = '@beldimalaki.com';

                return email.endsWith(domain) ? email.slice(0, -domain.length) : email;
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
                            Fiche commercial — cliquez sur un commercial pour afficher sa fiche
                        @elseif($isView && $formCommercial)
                            Fiche commercial — {{ $formCommercial->formattedCommercialId() }} · {{ $formCommercial->name }}
                        @elseif($isEdit)
                            Modifier — {{ $formCommercial->formattedCommercialId() }} · {{ $formCommercial->name }}
                        @else
                            Nouveau commercial
                        @endif
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                        @if(! $formActive)
                            Sélectionnez un commercial dans le tableau
                        @elseif($isView)
                            Cliquez sur « Modifier » pour changer une ou plusieurs informations
                        @elseif($isEdit)
                            Modifiez puis cliquez sur « Modifier » pour enregistrer
                        @else
                            Remplissez le formulaire puis cliquez sur « Valider »
                        @endif
                    </span>
                </div>

                @if($formCommercial && ($isView || $isEdit))
                    <div class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Cmd. livrées</p>
                                <p class="text-sm font-semibold tabular-nums">{{ $formCommercial->delivered_orders_count ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">CA vendu</p>
                                <p class="text-sm font-semibold tabular-nums">{{ number_format($formCommercial->total_sales ?? 0, 2, ',', ' ') }} DH</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Commissions</p>
                                <p class="text-sm font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{{ number_format($formCommercial->total_commissions ?? 0, 2, ',', ' ') }} DH</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-2 py-1.5">
                                <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Statut compte</p>
                                <p class="text-sm font-semibold {{ $formCommercial->is_active ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $formCommercial->is_active ? 'Actif' : 'Inactif' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="relative transition-opacity" :class="!formActive && 'opacity-55'">
                    <div
                        x-show="!formActive"
                        x-cloak
                        class="absolute inset-0 z-10 bg-slate-200/25 dark:bg-slate-900/20 cursor-not-allowed"
                    ></div>

                    <fieldset x-bind:disabled="!formActive || isView" class="border-0 p-0 m-0 min-w-0">
                        <div class="admin-order-form-bar">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-x-2 gap-y-2">
                                <div>
                                    <label class="admin-order-form-label">ID commercial</label>
                                    <input
                                        type="text"
                                        value="{{ $formCommercial?->formattedCommercialId() ?? $previewCommercialId }}"
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
                                <div class="sm:col-span-2 md:col-span-2">
                                    <label for="commercial_email_local" class="admin-order-form-label">Login (email) *</label>
                                    <div class="flex w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-sm overflow-hidden focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 dark:focus-within:border-brand-400 dark:focus-within:ring-brand-400">
                                        <input
                                            type="text"
                                            id="commercial_email_local"
                                            name="email_local"
                                            value="{{ $commercialEmailLocal }}"
                                            required
                                            autocomplete="username"
                                            placeholder="prenom.nom"
                                            class="flex-1 min-w-0 border-0 bg-transparent text-slate-900 dark:text-slate-100 text-sm py-1.5 px-2.5 focus:ring-0 focus:outline-none"
                                        >
                                        <span class="inline-flex items-center px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-100 text-sm font-semibold border-l border-slate-200 dark:border-slate-600 shrink-0 select-none whitespace-nowrap">
                                            @beldimalaki.com
                                        </span>
                                    </div>
                                    @error('email')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="admin-order-form-bar" @if($isView) x-cloak @elseif(! $formActive) x-cloak x-show="formActive" @endif>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-2 gap-y-2">
                                <div>
                                    <label for="commercial_password" class="admin-order-form-label">
                                        Mot de passe @unless($isEdit)*@endunless
                                    </label>
                                    <input
                                        type="password"
                                        id="commercial_password"
                                        name="password"
                                        @unless($isEdit) required @endunless
                                        placeholder="{{ $isEdit ? 'Laisser vide = inchangé' : 'Min. 8 caractères' }}"
                                        autocomplete="new-password"
                                        class="admin-order-form-input"
                                    >
                                    @error('password')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex items-end">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 cursor-pointer select-none pb-1.5">
                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                            @checked($commercialIsActive)
                                        >
                                        Compte actif (peut se connecter)
                                    </label>
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

                        @if($formActive)
                        <div class="admin-order-form-bar">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Autorisations d'accès</p>
                            @if($isView)
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-3">Lecture seule — cliquez sur « Modifier » pour changer les autorisations.</p>
                            @else
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-3">
                                    Définissez ce que ce commercial peut faire dans l'application (comme dans Paramètres &gt; Autorisations).
                                </p>
                            @endif
                            @include('settings.partials.permissions-groups-panel', [
                                'permissionGroups' => $permissionGroups ?? [],
                                'permissions' => $commercialPermissions ?? [],
                            ])
                        </div>
                        @endif
                    </fieldset>
                </div>

                <div class="admin-product-form-actions">
                    <div class="flex flex-wrap items-center justify-end gap-2 notranslate" translate="no">
                        @if(! $formActive)
                            <x-admin.action-btn
                                icon="plus"
                                label="Ajouter"
                                variant="success"
                                @click="window.location.href = '{{ $nouveauUrl }}'"
                            />
                        @elseif($isEdit)
                            <x-admin.action-btn type="submit" icon="edit" label="Modifier" variant="info" />
                        @elseif($isView)
                            <x-admin.action-btn
                                icon="edit"
                                label="Modifier"
                                variant="info"
                                @click="openCommercialEdit({{ $formCommercial->id }})"
                            />
                        @else
                            <x-admin.action-btn type="submit" icon="save" label="Valider" variant="success" />
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
                        <th class="text-center align-middle">ID commercial</th>
                        <th class="text-center align-middle">
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
                                                @click="openCommercialView(commercial.id); pickerOpen = false"
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
                        <th class="text-center align-middle">Contact</th>
                        <th class="text-center align-middle">Email</th>
                        <th class="text-center align-middle">WhatsApp</th>
                        <th class="text-center align-middle">Zone prospect</th>
                        @endunless
                        <th class="text-center align-middle">Commission affectée</th>
                        <th class="text-center align-middle">Cmd. livrées</th>
                        <th class="text-center align-middle">CA vendu</th>
                        <th class="text-center align-middle">Total commissions</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @if($isCommercialView)
                        @forelse($commercials as $commercial)
                            <tr>
                                <td class="admin-table-cell-muted font-mono text-xs text-center align-middle">{{ $commercial->formattedCommercialId() }}</td>
                                <td class="admin-table-cell font-medium text-center align-middle">{{ $commercial->name }}</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums">
                                    {{ number_format($commercial->effective_commission_rate ?? 0, 1, ',', ' ') }} %
                                </td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-semibold">{{ $commercial->delivered_orders_count ?? 0 }}</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-medium">{{ number_format($commercial->total_sales ?? 0, 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-semibold text-emerald-700 dark:text-emerald-400">
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
                        @forelse($commercials as $commercial)
                            <tr
                                class="admin-row-hover cursor-pointer"
                                :class="selectedId === {{ $commercial->id }} ? 'admin-row-selected' : ''"
                                @click="openCommercialView({{ $commercial->id }})"
                            >
                                <td class="admin-table-cell-muted font-mono text-xs text-center align-middle">{{ $commercial->formattedCommercialId() }}</td>
                                <td class="admin-table-cell font-medium text-center align-middle">{{ $commercial->name }}</td>
                                <td class="admin-table-cell text-center align-middle">{{ $commercial->phone ?: '—' }}</td>
                                <td class="admin-table-cell text-center align-middle">{{ $commercial->email }}</td>
                                <td class="admin-table-cell text-center align-middle">{{ $commercial->whatsapp ?: '—' }}</td>
                                <td class="admin-table-cell text-center align-middle">{{ $commercial->prospect_zone ?: '—' }}</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums">
                                    {{ number_format($commercial->effective_commission_rate ?? 0, 1, ',', ' ') }} %
                                </td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-semibold">{{ $commercial->delivered_orders_count ?? 0 }}</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-medium">{{ number_format($commercial->total_sales ?? 0, 2, ',', ' ') }} DH</td>
                                <td class="admin-table-cell text-center align-middle tabular-nums font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ number_format($commercial->total_commissions ?? 0, 2, ',', ' ') }} DH
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="admin-table-cell text-center text-slate-500 dark:text-slate-400 py-8">
                                    Aucun commercial. Cliquez sur « Ajouter » pour en créer un.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
