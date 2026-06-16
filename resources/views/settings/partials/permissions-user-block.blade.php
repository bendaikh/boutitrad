@php
    use App\Enums\UserRole;

    $isNew = $isNew ?? false;
    $disableRole = $disableRole ?? false;
    $disableActive = $disableActive ?? false;
    $role = $role ?: ($isNew ? UserRole::Commercial->value : null);
    $submitClass = $submitClass ?? 'bg-brand-600 hover:bg-brand-700';
@endphp

<div class="@if($isNew) border-2 border-brand-200 dark:border-brand-800 bg-brand-50/30 dark:bg-brand-900/10 @else border border-slate-200 dark:border-slate-700 @endif rounded-xl overflow-hidden">
    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if(! $isNew)
            @method('PUT')
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse min-w-[720px]">
                <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-xs w-[14%]">Nom utilisateur</th>
                        <th class="px-3 py-2 text-left font-semibold text-xs w-[14%]">Profil</th>
                        <th class="px-3 py-2 text-left font-semibold text-xs w-[22%]">Login</th>
                        <th class="px-3 py-2 text-left font-semibold text-xs w-[18%]">Mot de passe</th>
                        <th class="px-3 py-2 text-center font-semibold text-xs w-[10%]">Statut</th>
                        <th class="px-3 py-2 text-center font-semibold text-xs w-[10%]">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="align-top bg-white dark:bg-slate-900">
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700">
                            <input
                                type="text"
                                name="name"
                                value="{{ $name }}"
                                required
                                placeholder="Nom complet"
                                class="form-input w-full text-xs py-1.5"
                            >
                        </td>
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700">
                            @if($disableRole)
                                <input type="hidden" name="role" value="{{ $role }}">
                                <input type="text" value="{{ collect($roles)->first(fn ($r) => $r->value === $role)?->label() }}" readonly class="form-input w-full text-xs py-1.5 bg-slate-100 dark:bg-slate-800">
                            @else
                                <select name="role" required class="form-input w-full text-xs py-1.5">
                                    @foreach($roles as $roleOption)
                                        @if($isNew && $roleOption === UserRole::SuperAdmin)
                                            @continue
                                        @endif
                                        <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ $roleOption->label() }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700">
                            <input
                                type="email"
                                name="email"
                                value="{{ $email }}"
                                required
                                placeholder="email@exemple.com"
                                class="form-input w-full text-xs py-1.5"
                            >
                        </td>
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700">
                            <input
                                type="password"
                                name="password"
                                @if($isNew) required @endif
                                placeholder="{{ $isNew ? 'Min. 8 caractères' : 'Laisser vide = inchangé' }}"
                                class="form-input w-full text-xs py-1.5"
                                autocomplete="new-password"
                            >
                        </td>
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700 text-center">
                            @if($disableActive)
                                <input type="hidden" name="is_active" value="1">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-700">Actif</span>
                            @else
                                <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        class="rounded border-slate-300 text-brand-600"
                                        @checked($isActive)
                                    >
                                    Actif
                                </label>
                            @endif
                        </td>
                        <td class="px-3 py-3 border-t border-slate-200 dark:border-slate-700 text-center">
                            <button type="submit" class="px-3 py-1.5 text-white rounded-lg text-xs font-medium {{ $submitClass }}">
                                {{ $submitLabel }}
                            </button>
                        </td>
                    </tr>
                    <tr class="bg-white dark:bg-slate-900">
                        <td colspan="6" class="px-3 py-4 border-t border-slate-200 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-3">Autorisations d'accès</p>
                            @include('settings.partials.permissions-groups-panel', [
                                'permissionGroups' => $permissionGroups,
                                'permissions' => $permissions,
                                'isFullAccess' => $isFullAccess ?? false,
                            ])
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </form>

    @if(! $isNew && ($canDelete ?? false))
        <div class="px-3 py-2 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 text-right">
            <form method="POST" action="{{ route('settings.permissions.destroy', $userId) }}" class="inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:underline">Supprimer cet utilisateur</button>
            </form>
        </div>
    @endif
</div>
