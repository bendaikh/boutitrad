@php
    $isEdit = isset($client);
    $compact = $compact ?? false;
    $formActive = $formActive ?? true;
    $isActive = (bool) old('is_active', $client->is_active ?? true);
    $inputClass = $compact
        ? 'admin-product-form-input'
        : 'form-input w-full';
@endphp

<form
    id="client-form"
    method="POST"
    action="{{ $isEdit ? route('clients.update', $client) : route('clients.store') }}"
    enctype="multipart/form-data"
    class="admin-form-shell {{ $compact ? 'text-sm' : '' }}"
>
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if($compact)
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-2 {{ $formActive ? 'bg-slate-50 dark:bg-slate-800/60' : 'bg-slate-100 dark:bg-slate-800/80' }}">
            <h3 class="text-sm font-semibold {{ $formActive ? 'text-slate-800 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}">
                @if(! $formActive)
                    Saisie client — consultation
                @elseif($isEdit)
                    Modifier — {{ $client->formattedId() }} · {{ $client->name }}
                @else
                    Nouveau client
                @endif
            </h3>
            <span class="text-xs text-slate-500 hidden sm:inline">
                @if(! $formActive)
                    Cliquez sur « Nouveau client » ou « Modifier » pour activer
                @elseif($isEdit)
                    Modifiez puis cliquez sur Enregistrer en bas
                @else
                    ID attribué à l'enregistrement
                @endif
            </span>
        </div>

        <div
            class="p-3 max-h-[22vh] overflow-y-auto space-y-2 relative transition-opacity"
            :class="!formActive && 'opacity-55'"
        >
            <div
                x-show="!formActive"
                x-cloak
                class="absolute inset-0 z-10 bg-slate-200/25 cursor-not-allowed rounded"
            ></div>
            <fieldset x-bind:disabled="!formActive" class="border-0 p-0 m-0 min-w-0">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 xl:grid-cols-8 gap-2">
                <div class="col-span-2 sm:col-span-3 lg:col-span-2 flex items-center gap-2">
                    <div id="client-photo-preview" class="w-14 h-14 rounded-full border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center text-slate-400 shrink-0">
                        @if($isEdit && $client->photoUrl())
                            <img src="{{ $client->photoUrl() }}" alt="" class="w-full h-full object-cover">
                        @else
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <label for="photo" class="block text-[11px] font-medium text-slate-600 mb-0.5">Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/webp" class="block w-full text-[11px] text-slate-600 file:mr-1 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-brand-50 file:text-brand-700" onchange="previewClientPhoto(event)">
                        @error('photo')<p class="text-red-500 text-[11px]">{{ $message }}</p>@enderror
                        @if($isEdit && $client->photo)
                            <label class="inline-flex items-center gap-1 text-[11px] text-slate-600 mt-1">
                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-brand-600">
                                Supprimer
                            </label>
                        @endif
                    </div>
                </div>

                <div class="col-span-2 sm:col-span-3 lg:col-span-2">
                    <label for="name" class="block text-[11px] font-medium text-slate-600 mb-0.5">Nom client *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $client->name ?? '') }}" @if($formActive) required @endif class="{{ $inputClass }}">
                    @error('name')<p class="text-red-500 text-[11px]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-[11px] font-medium text-slate-600 mb-0.5">Téléphone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $client->phone ?? '') }}" class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="email" class="block text-[11px] font-medium text-slate-600 mb-0.5">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $client->email ?? '') }}" class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="city_id" class="block text-[11px] font-medium text-slate-600 mb-0.5">Ville livraison</label>
                    <select id="city_id" name="city_id" class="{{ $inputClass }}">
                        <option value="">— Sélectionner —</option>
                        @foreach($cities ?? [] as $cityOption)
                            <option value="{{ $cityOption->id }}" @selected(old('city_id', $client->city_id ?? '') == $cityOption->id)>{{ $cityOption->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id')<p class="text-red-500 text-[11px]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="is_active" class="block text-[11px] font-medium text-slate-600 mb-0.5">Statut</label>
                    <select id="is_active" name="is_active" class="{{ $inputClass }}">
                        <option value="1" @selected($isActive)>Actif</option>
                        <option value="0" @selected(! $isActive)>Inactif</option>
                    </select>
                </div>

                <div>
                    <label for="prospection" class="block text-[11px] font-medium text-slate-600 mb-0.5">Prospection</label>
                    <select id="prospection" name="prospection" class="{{ $inputClass }}">
                        <option value="">—</option>
                        @foreach($prospectionSources as $source)
                            <option value="{{ $source->value }}" @selected(old('prospection', $client->prospection?->value ?? '') === $source->value)>{{ $source->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payment_mode" class="block text-[11px] font-medium text-slate-600 mb-0.5">Mode paiement</label>
                    <select id="payment_mode" name="payment_mode" class="{{ $inputClass }}">
                        <option value="">—</option>
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->value }}" @selected(old('payment_mode', $client->payment_mode?->value ?? '') === $mode->value)>{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="commercial_id" class="block text-[11px] font-medium text-slate-600 mb-0.5">Commercial</label>
                    <select id="commercial_id" name="commercial_id" class="{{ $inputClass }}">
                        <option value="">—</option>
                        @foreach($commercials as $commercial)
                            <option value="{{ $commercial->id }}" @selected(old('commercial_id', $client->commercial_id ?? '') == $commercial->id)>{{ $commercial->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="facebook_page" class="block text-[11px] font-medium text-slate-600 mb-0.5">Facebook</label>
                    <input type="text" id="facebook_page" name="facebook_page" value="{{ old('facebook_page', $client->facebook_page ?? '') }}" placeholder="facebook.com/..." class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="instagram_page" class="block text-[11px] font-medium text-slate-600 mb-0.5">Instagram</label>
                    <input type="text" id="instagram_page" name="instagram_page" value="{{ old('instagram_page', $client->instagram_page ?? '') }}" placeholder="instagram.com/..." class="{{ $inputClass }}">
                </div>

                <div class="col-span-2 sm:col-span-3 lg:col-span-6 xl:col-span-8">
                    <label for="address" class="block text-[11px] font-medium text-slate-600 mb-0.5">Adresse</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $client->address ?? '') }}" class="{{ $inputClass }}">
                </div>
            </div>
            </fieldset>
        </div>

        <div class="px-3 py-2 border-t border-slate-100 bg-slate-50/90 flex flex-wrap items-end gap-2 notranslate" translate="no">
            <fieldset x-bind:disabled="!formActive" class="flex-1 min-w-[10rem] border-0 p-0 m-0 min-w-0 transition-opacity" :class="!formActive && 'opacity-55'">
                <label for="notes" class="block text-[11px] font-medium text-slate-600 mb-0.5">Notes</label>
                <input type="text" id="notes" name="notes" value="{{ old('notes', $client->notes ?? '') }}" class="{{ $inputClass }}">
            </fieldset>
            @include('clients.partials.form-actions')
        </div>

    @else
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $isEdit ? 'Modifier la fiche client' : 'Nouvelle fiche client' }}</h3>
            <p class="text-xs text-slate-500 mt-1">Complétez les informations ci-dessous pour enregistrer le client.</p>
        </div>

        <div class="p-5 grid grid-cols-1 xl:grid-cols-12 gap-5">
            <div class="xl:col-span-3 flex flex-col items-center justify-start gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-5">
                <div id="client-photo-preview" class="w-32 h-32 rounded-full border-2 border-slate-200 bg-white overflow-hidden flex items-center justify-center text-slate-400 shrink-0">
                    @if($isEdit && $client->photoUrl())
                        <img src="{{ $client->photoUrl() }}" alt="{{ $client->name }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    @endif
                </div>
                <div class="w-full space-y-2 text-center">
                    <label for="photo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">Importer photo</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/jpg,image/png,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" onchange="previewClientPhoto(event)">
                    <p class="text-xs text-slate-400">JPG, PNG ou WEBP — 2 Mo max.</p>
                    @error('photo')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                    @if($isEdit && $client->photo)
                        <label class="inline-flex items-center gap-2 text-xs text-slate-600 mt-2">
                            <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-brand-600">
                            Supprimer la photo
                        </label>
                    @endif
                </div>
            </div>

            <div class="xl:col-span-9 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">ID client</label>
                    @if($isEdit)
                        <div class="px-3 py-2 rounded-lg bg-slate-100 font-mono text-sm text-brand-700">{{ $client->formattedId() }}</div>
                    @else
                        <div class="px-3 py-2 rounded-lg bg-slate-100 text-sm text-slate-500 italic">Attribué automatiquement</div>
                    @endif
                </div>

                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="name" class="block text-xs font-medium text-slate-600 mb-1">Nom client *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $client->name ?? '') }}" required class="{{ $inputClass }}">
                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-xs font-medium text-slate-600 mb-1">Téléphone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $client->phone ?? '') }}" placeholder="+212 6..." class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="email" class="block text-xs font-medium text-slate-600 mb-1">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $client->email ?? '') }}" class="{{ $inputClass }}">
                </div>

                <div>
                    <label for="city_id" class="block text-xs font-medium text-slate-600 mb-1">Ville livraison (Cathedis)</label>
                    <select id="city_id" name="city_id" class="{{ $inputClass }}">
                        <option value="">— Sélectionner une ville —</option>
                        @foreach($cities ?? [] as $cityOption)
                            <option value="{{ $cityOption->id }}" @selected(old('city_id', $client->city_id ?? '') == $cityOption->id)>{{ $cityOption->name }} ({{ $cityOption->zone->label() }})</option>
                        @endforeach
                    </select>
                    @error('city_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="prospection" class="block text-xs font-medium text-slate-600 mb-1">Prospection</label>
                    <select id="prospection" name="prospection" class="{{ $inputClass }}">
                        <option value="">— Sélectionner —</option>
                        @foreach($prospectionSources as $source)
                            <option value="{{ $source->value }}" @selected(old('prospection', $client->prospection?->value ?? '') === $source->value)>{{ $source->label() }}</option>
                        @endforeach
                    </select>
                    @error('prospection')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="payment_mode" class="block text-xs font-medium text-slate-600 mb-1">Mode paiement</label>
                    <select id="payment_mode" name="payment_mode" class="{{ $inputClass }}">
                        <option value="">— Sélectionner —</option>
                        @foreach($paymentModes as $mode)
                            <option value="{{ $mode->value }}" @selected(old('payment_mode', $client->payment_mode?->value ?? '') === $mode->value)>{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                    @error('payment_mode')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="commercial_id" class="block text-xs font-medium text-slate-600 mb-1">Commercial affecté</label>
                    <select id="commercial_id" name="commercial_id" class="{{ $inputClass }}">
                        <option value="">— Aucun —</option>
                        @foreach($commercials as $commercial)
                            <option value="{{ $commercial->id }}" @selected(old('commercial_id', $client->commercial_id ?? '') == $commercial->id)>{{ $commercial->name }}</option>
                        @endforeach
                    </select>
                    @error('commercial_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="is_active" class="block text-xs font-medium text-slate-600 mb-1">Statut</label>
                    <select id="is_active" name="is_active" class="{{ $inputClass }}">
                        <option value="1" @selected($isActive)>Actif</option>
                        <option value="0" @selected(! $isActive)>Inactif</option>
                    </select>
                </div>

                <div>
                    <label for="facebook_page" class="block text-xs font-medium text-slate-600 mb-1">Page Facebook</label>
                    <input type="text" id="facebook_page" name="facebook_page" value="{{ old('facebook_page', $client->facebook_page ?? '') }}" placeholder="facebook.com/..." class="{{ $inputClass }}">
                    @error('facebook_page')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="instagram_page" class="block text-xs font-medium text-slate-600 mb-1">Page Instagram</label>
                    <input type="text" id="instagram_page" name="instagram_page" value="{{ old('instagram_page', $client->instagram_page ?? '') }}" placeholder="instagram.com/..." class="{{ $inputClass }}">
                    @error('instagram_page')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="xl:col-span-12 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label for="address" class="block text-xs font-medium text-slate-600 mb-1">Adresse</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $client->address ?? '') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="notes" class="block text-xs font-medium text-slate-600 mb-1">Notes internes</label>
                    <textarea id="notes" name="notes" rows="2" class="{{ $inputClass }}">{{ old('notes', $client->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex gap-3">
            <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700">Enregistrer</button>
            <a href="{{ route('clients.index') }}" class="px-5 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm hover:bg-slate-200">Annuler</a>
        </div>
    @endif
</form>

@once
    @push('scripts')
    <script>
        function previewClientPhoto(event) {
            const file = event.target.files?.[0];
            const preview = document.getElementById('client-photo-preview');
            if (!file || !preview) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu" class="w-full h-full object-cover">`;
            };
            reader.readAsDataURL(file);
        }
    </script>
    @endpush
@endonce
