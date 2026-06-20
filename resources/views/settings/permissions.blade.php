@php
    use App\Support\PermissionCatalog;

    $superAdminCount = $users->filter(fn ($u) => $u->isSuperAdmin())->count();
@endphp

<x-admin-layout title="Autorisations">
    <div class="space-y-5 mb-4">
        @if(session('success'))
            <div class="admin-flash-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-flash-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-flash-error">
                <p class="font-semibold mb-1">Veuillez corriger les erreurs :</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-card p-4">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Gestion des autorisations</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Saisissez l'utilisateur puis cochez les droits par module : Tableau de bord, Clients, Stock, Ventes, Configuration.
            </p>
        </div>

        <div>
            <h3 class="text-sm font-bold text-brand-800 dark:text-brand-200 mb-2 px-1">Nouvel utilisateur</h3>
            @include('settings.partials.permissions-user-block', [
                'formAction' => route('settings.permissions.store'),
                'name' => old('name'),
                'email' => old('email'),
                'emailLocal' => old('email_local', \App\Support\CommercialEmail::localPart(old('email'))),
                'role' => old('role'),
                'permissions' => old('permissions', []),
                'isActive' => old('is_active', true),
                'isNew' => true,
                'isFullAccess' => false,
                'roles' => $roles,
                'permissionGroups' => $permissionGroups,
                'submitLabel' => 'Enregistrer',
                'submitClass' => 'bg-emerald-600 hover:bg-emerald-700',
            ])
        </div>

        <div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-2 px-1">Utilisateurs existants</h3>
            <div class="space-y-4">
                @foreach($users as $user)
                    @php
                        $userPermissions = $user->permissions ?? PermissionCatalog::defaultsForRole($user->role->value);
                        $isFullAccess = $user->isSuperAdmin();
                        $canDelete = ! $user->isSuperAdmin() || $superAdminCount > 1;
                    @endphp
                    @include('settings.partials.permissions-user-block', [
                        'formAction' => route('settings.permissions.update', $user),
                        'userId' => $user->id,
                        'name' => old('name', $user->name),
                        'email' => old('email', $user->email),
                        'emailLocal' => old('email_local', \App\Support\CommercialEmail::localPart(old('email', $user->email))),
                        'role' => old('role', $user->role->value),
                        'permissions' => $isFullAccess ? PermissionCatalog::keys() : $userPermissions,
                        'isActive' => old('is_active', $user->is_active),
                        'isNew' => false,
                        'isFullAccess' => $isFullAccess,
                        'roles' => $roles,
                        'permissionGroups' => $permissionGroups,
                        'submitLabel' => 'Enregistrer',
                        'submitClass' => 'bg-brand-600 hover:bg-brand-700',
                        'disableRole' => $user->isSuperAdmin() && $superAdminCount <= 1,
                        'disableActive' => $user->isSuperAdmin(),
                        'canDelete' => $canDelete,
                    ])
                @endforeach
            </div>
        </div>
    </div>
</x-admin-layout>
