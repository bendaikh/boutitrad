<x-admin-layout title="Utilisateurs">
    <div class="flex justify-end mb-4"><a href="{{ route('users.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">+ Nouvel utilisateur</a></div>
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Nom</th><th class="px-5 py-3 text-left">Email</th><th class="px-5 py-3 text-left">Rôle</th><th class="px-5 py-3 text-center">Actif</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y">
                @foreach($users as $user)
                    <tr><td class="px-5 py-3 font-medium">{{ $user->name }}</td><td class="px-5 py-3">{{ $user->email }}</td><td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700">{{ $user->role->label() }}</span></td><td class="px-5 py-3 text-center">{{ $user->is_active ? '✓' : '✗' }}</td><td class="px-5 py-3 text-right"><a href="{{ route('users.edit', $user) }}" class="text-indigo-600">Modifier</a></td></tr>
                @endforeach
            </tbody>
        </table>
        @if($users->hasPages())<div class="px-5 py-3 border-t">{{ $users->links() }}</div>@endif
    </div>
</x-admin-layout>
