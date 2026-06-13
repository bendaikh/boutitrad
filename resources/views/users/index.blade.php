<x-admin-layout title="Utilisateurs">
    <x-admin.list-page>
        <x-slot:toolbar>
            <div class="flex justify-end">
                <a href="{{ route('users.create') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm">+ Nouvel utilisateur</a>
            </div>
        </x-slot:toolbar>

        <x-admin.data-table class="flex-1 min-h-0">
            @if($users->hasPages())
                <x-slot:footer>{{ $users->links() }}</x-slot:footer>
            @endif
            <thead><tr><th class="text-left">Nom</th><th class="text-left">Email</th><th class="text-left">Rôle</th><th class="text-center">Actif</th><th class="text-right">Actions</th></tr></thead>
            <tbody class="divide-y">
                @foreach($users as $user)
                    <tr><td class="px-5 py-3 font-medium">{{ $user->name }}</td><td class="px-5 py-3">{{ $user->email }}</td><td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-brand-100 text-brand-700">{{ $user->role->label() }}</span></td><td class="px-5 py-3 text-center">{{ $user->is_active ? '✓' : '✗' }}</td><td class="px-5 py-3 text-right"><a href="{{ route('users.edit', $user) }}" class="text-brand-600">Modifier</a></td></tr>
                @endforeach
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
