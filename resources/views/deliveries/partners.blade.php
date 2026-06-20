<x-admin-layout title="Partenaires">
    <div class="space-y-5">
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-2">Intégration Cathedis</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm mb-4">
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
                    <p class="text-xs text-slate-500">Envoi commandes prêt</p>
                    <p class="font-semibold {{ ($cathedis['dispatch_ready'] ?? false) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ($cathedis['dispatch_ready'] ?? false) ? 'Oui' : 'Non' }}</p>
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
            @if(!empty($cathedis['missing']))
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30 p-3 text-sm text-amber-800 dark:text-amber-200">
                    <p class="font-medium mb-1">Configuration manuelle requise</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($cathedis['missing'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(auth()->user()->isSuperAdmin())
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('deliveries.cathedis.sync-cities') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 btn-dark text-sm">Synchroniser les villes</button>
                    </form>
                    <form method="POST" action="{{ route('deliveries.cathedis.sync-orders') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 btn-dark text-sm">Synchroniser les statuts commandes</button>
                    </form>
                    <form method="POST" action="{{ route('deliveries.cathedis.test') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm hover:bg-sky-700">Tester la connexion</button>
                    </form>
                </div>

                <div class="mt-4 rounded-lg border border-slate-200 dark:border-slate-700 p-4 max-w-2xl">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-2">Configuration Cathedis (manuelle)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                        Chaque compte Cathedis a ses propres identifiants et paramètres. Renseignez-les ici — rien n'est prérempli automatiquement depuis le <code class="text-xs">.env</code>.
                    </p>
                    <form method="POST" action="{{ route('deliveries.cathedis.config') }}" class="space-y-4">
                        @csrf
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="cathedis_enabled" value="1" class="rounded border-slate-300 text-brand-600" @checked(old('cathedis_enabled', $cathedisConfig['enabled'] ?? false))>
                            API Cathedis activée
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Email / identifiant</label>
                                <input type="email" name="cathedis_username" value="{{ old('cathedis_username', $cathedisConfig['username'] ?? '') }}" class="form-input w-full text-sm" placeholder="compte@cathedis.delivery">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Mot de passe</label>
                                <input type="password" name="cathedis_password" class="form-input w-full text-sm" placeholder="Laisser vide pour conserver l'actuel">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Token API (optionnel)</label>
                            <input type="password" name="cathedis_api_token" class="form-input w-full text-sm" placeholder="Uniquement si Cathedis vous a fourni un token">
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mb-2">Paramètres d'envoi (spécifiques à votre compte)</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium mb-1">ID magasin Cathedis</label>
                                    <input type="number" name="cathedis_store_id" value="{{ old('cathedis_store_id', $cathedisConfig['store_id'] ?? '') }}" class="form-input w-full text-sm" placeholder="ex. 23055" min="1">
                                    <p class="text-xs text-slate-500 mt-1">Visible dans Cathedis → votre magasin / store.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">ID secteur par défaut</label>
                                    <input type="number" name="cathedis_default_sector_id" value="{{ old('cathedis_default_sector_id', $cathedisConfig['default_sector_id'] ?? '') }}" class="form-input w-full text-sm" placeholder="ex. 2766" min="1">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Nom secteur par défaut</label>
                                    <input type="text" name="cathedis_default_sector_name" value="{{ old('cathedis_default_sector_name', $cathedisConfig['default_sector_name'] ?? '') }}" class="form-input w-full text-sm" placeholder="ex. Autre">
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-sm mt-5">
                                        <input type="checkbox" name="cathedis_allow_opening" value="1" class="rounded border-slate-300 text-brand-600" @checked(old('cathedis_allow_opening', $cathedisConfig['allow_opening'] ?? false))>
                                        Autoriser l'ouverture du colis
                                    </label>
                                </div>
                            </div>
                        </div>

                        <details class="text-sm">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">Options avancées (laisser par défaut sauf indication Cathedis)</summary>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Type paiement (ID)</label>
                                    <input type="number" name="cathedis_payment_type_id" value="{{ old('cathedis_payment_type_id', $cathedisConfig['payment_type_id'] ?? 1) }}" class="form-input w-full text-sm" min="1">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Type livraison (ID)</label>
                                    <input type="number" name="cathedis_delivery_type_id" value="{{ old('cathedis_delivery_type_id', $cathedisConfig['delivery_type_id'] ?? 1) }}" class="form-input w-full text-sm" min="1">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Statut initial (ID)</label>
                                    <input type="number" name="cathedis_delivery_status_id" value="{{ old('cathedis_delivery_status_id', $cathedisConfig['delivery_status_id'] ?? 1) }}" class="form-input w-full text-sm" min="1">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Statut initial (code)</label>
                                    <input type="text" name="cathedis_delivery_status_code" value="{{ old('cathedis_delivery_status_code', $cathedisConfig['delivery_status_code'] ?? 'En Attente Ramassage') }}" class="form-input w-full text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Poids</label>
                                    <input type="text" name="cathedis_range_weight" value="{{ old('cathedis_range_weight', $cathedisConfig['range_weight'] ?? 'ONE_FIVE') }}" class="form-input w-full text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Méthode expédition</label>
                                    <input type="text" name="cathedis_shipping_method" value="{{ old('cathedis_shipping_method', $cathedisConfig['shipping_method'] ?? 'LAD') }}" class="form-input w-full text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Type colis</label>
                                    <input type="text" name="cathedis_type_delivery" value="{{ old('cathedis_type_delivery', $cathedisConfig['type_delivery'] ?? 'NORMAL') }}" class="form-input w-full text-sm">
                                </div>
                            </div>
                        </details>

                        <button type="submit" class="px-4 py-2 btn-dark text-sm">Enregistrer la configuration</button>
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
                    La configuration du compte Cathedis (identifiants, magasin, secteur) se fait dans le formulaire ci-dessus.
                    L'URL API technique peut rester <code class="text-xs">https://api.cathedis.delivery</code> sauf indication contraire.
                </p>
            </div>
        @endif
    </div>
</x-admin-layout>
