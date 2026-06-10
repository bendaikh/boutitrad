<x-admin-layout title="{{ isset($user) ? 'Modifier utilisateur' : 'Nouvel utilisateur' }}">
    <form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" class="max-w-lg bg-white rounded-xl border p-6 shadow-sm space-y-4">
        @csrf @if(isset($user)) @method('PUT') @endif
        <div><label class="block text-sm font-medium mb-1">Nom</label><input type="text" name="name" value="{{ old('name', isset($user) ? $user->name : '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium mb-1">Mot de passe {{ isset($user) ? '(laisser vide pour conserver)' : '*' }}</label><input type="password" name="password" {{ isset($user) ? '' : 'required' }} class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div><label class="block text-sm font-medium mb-1">Rôle</label><select name="role" required class="w-full rounded-lg border-slate-300 text-sm">@foreach($roles as $r)<option value="{{ $r->value }}" @selected(old('role', isset($user) ? $user->role->value : '') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium mb-1">Téléphone</label><input type="text" name="phone" value="{{ old('phone', isset($user) ? $user->phone : '') }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', isset($user) ? $user->is_active : true)) class="rounded text-indigo-600"> Actif</label>
        <div class="flex gap-3"><button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm">Enregistrer</button><a href="{{ route('users.index') }}" class="px-5 py-2 bg-slate-100 rounded-lg text-sm">Annuler</a></div>
    </form>
</x-admin-layout>
