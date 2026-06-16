<x-admin-layout title="Partenaires">
    <div class="space-y-5">
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Partenaires de livraison</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Les commandes validées par l'admin sont transmises automatiquement au partenaire sélectionné (ex. Cathedis) pour livraison et encaissement COD.
            </p>

            <x-admin.data-table compact>
                <thead>
                    <tr>
                        <th class="text-left">Nom</th>
                        <th class="text-left">Code</th>
                        <th class="text-left">Contact</th>
                        <th class="text-center">Par défaut</th>
                        <th class="text-center">Actif</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($partners as $partner)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="admin-table-cell font-medium">{{ $partner->name }}</td>
                            <td class="admin-table-cell font-mono text-xs">{{ $partner->code }}</td>
                            <td class="admin-table-cell text-sm">
                                {{ $partner->contact_email ?? '—' }}
                                @if($partner->contact_phone)<br><span class="text-slate-500">{{ $partner->contact_phone }}</span>@endif
                            </td>
                            <td class="admin-table-cell text-center">
                                @if($partner->is_default)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-brand-100 text-brand-700">Oui</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="admin-table-cell text-center">
                                @if($partner->is_active)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">Actif</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600">Inactif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun partenaire enregistré</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>

        @if(auth()->user()->isSuperAdmin())
            <div class="admin-card p-6 max-w-xl">
                <h3 class="font-semibold mb-4">Ajouter un partenaire</h3>
                <form method="POST" action="{{ route('deliveries.partners.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium mb-1">Nom</label>
                        <input type="text" name="name" required class="form-input w-full text-sm" placeholder="Cathedis">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Code</label>
                        <input type="text" name="code" required class="form-input w-full text-sm font-mono" placeholder="cathedis">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">Email</label>
                            <input type="email" name="contact_email" class="form-input w-full text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Téléphone</label>
                            <input type="text" name="contact_phone" class="form-input w-full text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">URL API</label>
                        <input type="url" name="api_url" class="form-input w-full text-sm" placeholder="https://api.cathedis.ma">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Token API</label>
                        <input type="password" name="api_token" class="form-input w-full text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300">
                        Partenaire par défaut
                    </label>
                    <button type="submit" class="px-4 py-2 btn-dark text-sm">Enregistrer</button>
                </form>
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    API Cathedis : configurez aussi <code class="text-xs">CATHEDIS_ENABLED=true</code> et <code class="text-xs">CATHEDIS_API_TOKEN</code> dans le fichier <code class="text-xs">.env</code>.
                </p>
            </div>
        @endif
    </div>
</x-admin-layout>
