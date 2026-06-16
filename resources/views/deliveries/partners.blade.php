<x-admin-layout title="Partenaires">
    <div class="space-y-5">
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Intégration Cathedis</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm mb-4">
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                    <p class="text-xs text-slate-500">API activée</p>
                    <p class="font-semibold {{ ($cathedis['enabled'] ?? false) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ($cathedis['enabled'] ?? false) ? 'Oui' : 'Non' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                    <p class="text-xs text-slate-500">Identifiants configurés</p>
                    <p class="font-semibold {{ ($cathedis['configured'] ?? false) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ($cathedis['configured'] ?? false) ? 'Oui' : 'Non' }}</p>
                    @if(!empty($cathedis['auth_mode']))
                        <p class="text-xs text-slate-500 mt-1">Mode : {{ $cathedis['auth_mode'] === 'login' ? 'connexion web' : 'token API' }}</p>
                    @endif
                </div>
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                    <p class="text-xs text-slate-500">Villes en système</p>
                    <p class="font-semibold">{{ $cathedis['cities_count'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                    <p class="text-xs text-slate-500">URL API</p>
                    <p class="font-mono text-xs break-all">{{ $cathedis['api_url'] ?? '—' }}</p>
                </div>
            </div>
            @if(auth()->user()->isSuperAdmin())
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('deliveries.cathedis.sync-cities') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 btn-dark text-sm">Synchroniser les villes</button>
                    </form>
                    <form method="POST" action="{{ route('deliveries.cathedis.test') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Tester la connexion</button>
                    </form>
                </div>
            @endif
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                Circuit : commercial envoie → admin modifie/valide → transmission automatique Cathedis à la validation.
            </p>
        </div>

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
                        <input type="url" name="api_url" class="form-input w-full text-sm" placeholder="https://api.cathedis.delivery">
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
                    API Cathedis : dans <code class="text-xs">.env</code>, mettez <code class="text-xs">CATHEDIS_ENABLED=true</code> et vos identifiants de connexion
                    (<code class="text-xs">CATHEDIS_USERNAME</code> + <code class="text-xs">CATHEDIS_PASSWORD</code> — les mêmes que sur
                    <a href="https://api.cathedis.delivery/" target="_blank" rel="noopener" class="underline">api.cathedis.delivery</a>).
                    Un token API (<code class="text-xs">CATHEDIS_API_TOKEN</code>) n'est requis que si Cathedis vous en a fourni un.
                </p>
            </div>
        @endif
    </div>
</x-admin-layout>
