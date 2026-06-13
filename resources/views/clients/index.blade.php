<x-admin-layout title="Fiche client">
    @php
        $formClient = $editingClient ?? null;
        $formActive = $formActive || $errors->any();
        $initialSelectedId = $editingClient?->id;
        $filterParams = request()->only(['search', 'status']);
        $nouveauUrl = route('clients.index', array_merge($filterParams, ['new' => 1]));
        $annulerUrl = route('clients.index', $filterParams);
    @endphp
    <div
        x-data="{
            formActive: {{ $formActive ? 'true' : 'false' }},
            selectedId: {{ $initialSelectedId ? $initialSelectedId : 'null' }},
            printUrl: '{{ url('clients') }}',
            editUrl: '{{ route('clients.index', $filterParams) }}',
            deleteAction: '',
        }"
        x-init="
            $watch('selectedId', id => {
                deleteAction = id ? `${printUrl}/${id}` : '';
            });
        "
    >
        <x-admin.list-page>
            <x-slot:toolbar>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <form method="GET" class="flex gap-2 flex-wrap flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="flex-1 min-w-[10rem] rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                        <select name="status" class="rounded-lg border-slate-300 text-sm">
                            <option value="">Tous</option>
                            <option value="active" @selected(request('status') === 'active')>Actifs</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option>
                        </select>
                        <button type="submit" class="px-4 py-2 btn-dark">Filtrer</button>
                    </form>

                    <div class="flex justify-end items-center gap-2 shrink-0 notranslate" translate="no">
                        <x-admin.action-btn
                            icon="cancel"
                            label="Annuler"
                            x-bind:disabled="!formActive"
                            @click="window.location.href = '{{ $annulerUrl }}'"
                        />
                        <x-admin.action-btn
                            icon="plus"
                            label="Nouveau client"
                            variant="primary"
                            @click="window.location.href = '{{ $nouveauUrl }}'"
                        />
                    </div>
                </div>
            </x-slot:toolbar>

            <form id="client-delete-form" method="POST" x-bind:action="deleteAction" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <div class="shrink-0 max-h-[36vh]">
                @include('clients.form', [
                    'client' => $formClient,
                    'compact' => true,
                    'formActive' => $formActive,
                ])
            </div>

            <x-admin.data-table min-width="1020px" class="flex-1 min-h-0">
                @if($clients->hasPages())
                    <x-slot:footer>{{ $clients->links() }}</x-slot:footer>
                @endif
                <thead>
                    <tr>
                        <th class="text-left">ID client</th>
                        <th class="text-left">Nom client</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Ville</th>
                        <th class="text-left">Prospection</th>
                        <th class="text-left">Mode paiement</th>
                        <th class="text-left">Commercial affecté</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse($clients as $client)
                        <tr
                            class="admin-row-hover"
                            :class="selectedId === {{ $client->id }} ? 'admin-row-selected' : ''"
                            @click="selectedId = {{ $client->id }}"
                            @dblclick="window.location.href = editUrl + (editUrl.includes('?') ? '&' : '?') + 'edit={{ $client->id }}'"
                        >
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $client->formattedId() }}</td>
                            <td class="admin-table-cell font-medium">{{ $client->name }}</td>
                            <td class="admin-table-cell">{{ $client->phone ?? $client->email ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $client->city ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $client->prospection?->label() ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $client->payment_mode?->label() ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $client->commercial?->name ?? '—' }}</td>
                            <td class="admin-table-cell text-center">
                                @if($client->is_active)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Actif</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Inactif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="admin-table-cell text-center text-slate-500 dark:text-slate-400">Aucun client</td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
