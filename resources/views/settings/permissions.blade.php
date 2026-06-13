<x-admin-layout title="Autorisations">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div>
                <h2 class="text-lg font-semibold text-slate-800 mb-2">Gestion des autorisations</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Définissez les droits d'accès par rôle et par utilisateur.</p>
            </div>
        </x-slot:toolbar>

        <x-admin.data-table class="flex-1 min-h-0">
            <thead>
                <tr>
                    <th class="text-left">Utilisateur</th>
                    <th class="text-left">Rôle</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-3">{{ $user->role->label() }}</td>
                        <td class="px-5 py-3 text-center">{{ $user->is_active ? 'Actif' : 'Inactif' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
