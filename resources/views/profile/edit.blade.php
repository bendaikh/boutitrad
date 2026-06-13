<x-admin-layout title="Mon profil">
    <div class="max-w-2xl space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-1">Informations du profil</h2>
            <p class="text-sm text-slate-500 mb-6">Mettez à jour votre photo, votre nom et votre adresse e-mail.</p>

            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('patch')

                <div class="flex items-center gap-5">
                    <x-admin.user-avatar :user="$user" size="lg" id="profile-photo-preview" />
                    <div class="space-y-3 flex-1">
                        <div>
                            <label for="profile_photo" class="block text-sm font-medium text-slate-700 mb-1.5">Photo de profil</label>
                            <input
                                id="profile_photo"
                                name="profile_photo"
                                type="file"
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                                onchange="previewProfilePhoto(event)"
                            >
                            <p class="mt-1 text-xs text-slate-400">JPG, PNG ou WEBP — 2 Mo max.</p>
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($user->profile_photo)
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                Supprimer la photo actuelle
                            </label>
                        @endif
                    </div>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nom</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                        class="form-input w-full">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                        class="form-input w-full">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="btn-primary">Enregistrer</button>
                    @if (session('status') === 'profile-updated')
                        <p class="text-sm text-emerald-600">Profil mis à jour.</p>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function previewProfilePhoto(event) {
                const file = event.target.files[0];
                const preview = document.getElementById('profile-photo-preview');

                if (!file || !preview) {
                    return;
                }

                preview.outerHTML = `<img id="profile-photo-preview" src="${URL.createObjectURL(file)}" alt="Aperçu" class="rounded-full object-cover ring-2 ring-brand-100 shrink-0 w-20 h-20 text-xl">`;
            }
        </script>
    @endpush
</x-admin-layout>
