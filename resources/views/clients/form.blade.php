<x-admin-layout title="{{ isset($client) ? 'Modifier client' : 'Nouveau client' }}">
    <div class="max-w-2xl">
        <form method="POST" action="{{ isset($client) ? route('clients.update', $client) : route('clients.store') }}" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            @csrf
            @if(isset($client)) @method('PUT') @endif

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone ?? '') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Adresse</label>
                <input type="text" name="address" value="{{ old('address', $client->address ?? '') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city', $client->city ?? '') }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Solde (DH)</label>
                    <input type="number" step="0.01" name="balance" value="{{ old('balance', $client->balance ?? 0) }}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes internes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('notes', $client->notes ?? '') }}</textarea>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active ?? true)) class="rounded border-slate-300 text-brand-600">
                <span class="text-sm text-slate-700">Client actif</span>
            </label>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Enregistrer</button>
                <a href="{{ route('clients.index') }}" class="px-5 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200">Annuler</a>
            </div>
        </form>
    </div>
</x-admin-layout>
