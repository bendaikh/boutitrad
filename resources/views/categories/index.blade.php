<x-admin-layout title="Catégories & Marques">
    @php
        $categoryForm = $editingCategory ?? null;
        $brandForm = $editingBrand ?? null;
        $categoryActive = (bool) old('is_active', $categoryForm->is_active ?? true);
        $brandActive = (bool) old('is_active', $brandForm->is_active ?? true);
    @endphp
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div
            class="admin-panel flex flex-col"
            x-data="{
                selectedCategoryId: {{ $categoryForm?->id ?? 'null' }},
                categoryDeleteAction: '',
            }"
            x-init="$watch('selectedCategoryId', id => { categoryDeleteAction = id ? '{{ url('categories') }}/' + id : '' })"
        >
            <div class="admin-panel-header">
                <h3 class="text-sm font-bold text-brand-900 dark:text-brand-200">
                    {{ $categoryForm ? 'Modifier la catégorie' : 'Catégories' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $categoryForm ? $categoryForm->name : 'Gérez les catégories produits' }}
                </p>
            </div>

            <form
                id="category-form"
                method="POST"
                action="{{ $categoryForm ? route('categories.update', $categoryForm) : route('categories.store') }}"
                enctype="multipart/form-data"
                class="admin-panel-form"
            >
                @csrf
                @if($categoryForm) @method('PUT') @endif
                <div class="flex gap-3 items-start">
                    <div class="shrink-0 w-20 flex flex-col gap-1.5">
                        <div class="w-20 h-20 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center" id="category-image-preview">
                            @if($categoryForm?->imageUrl())
                                <img src="{{ $categoryForm->imageUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <label for="category_image" class="block w-full cursor-pointer rounded-md border border-brand-200 dark:border-brand-700 bg-brand-50 dark:bg-brand-900/40 px-1 py-1.5 text-center text-[10px] font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-900/60 transition-colors">
                            Choisir fichier
                            <input type="file" id="category_image" name="category_image" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="sr-only" onchange="previewImage(event, 'category-image-preview')">
                        </label>
                        @error('category_image')<p class="text-red-500 text-[10px] text-center">{{ $message }}</p>@enderror
                        <p class="text-[9px] text-slate-400 text-center leading-tight">JPG, PNG, WebP · max. 5 Mo</p>
                    </div>
                    <div class="flex-1 min-w-0 space-y-2">
                        <div>
                            <label for="category_name" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Nom catégorie *</label>
                            <input type="text" id="category_name" name="name" value="{{ old('name', $categoryForm->name ?? '') }}" required placeholder="Ex. Électronique" class="form-input w-full py-1.5">
                            @error('name')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="category_description" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Description</label>
                            <input type="text" id="category_description" name="description" value="{{ old('description', $categoryForm->description ?? '') }}" placeholder="Description de la catégorie" class="form-input w-full py-1.5">
                            @error('description')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="category_is_active" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Actions</label>
                            <select id="category_is_active" name="is_active" class="form-input w-full py-1.5">
                                <option value="1" @selected($categoryActive)>Actif</option>
                                <option value="0" @selected(! $categoryActive)>Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-2 notranslate" translate="no">
                    @if($categoryForm)
                        <x-admin.action-btn href="{{ route('categories.index') }}" icon="cancel" label="Annuler" />
                        <x-admin.action-btn type="submit" icon="save" label="Valider" variant="success" />
                    @else
                        <x-admin.action-btn type="submit" icon="plus" label="Ajouter" variant="primary" />
                    @endif
                    <x-admin.action-btn
                        icon="edit"
                        label="Modifier"
                        x-bind:disabled="!selectedCategoryId"
                        @click="selectedCategoryId && (window.location.href = '{{ route('categories.index') }}?edit_category=' + selectedCategoryId)"
                    />
                    <x-admin.action-btn
                        icon="delete"
                        label="Supprimer"
                        variant="danger"
                        x-bind:disabled="!selectedCategoryId"
                        @click="if (selectedCategoryId && confirm('Supprimer cette catégorie ?')) { document.getElementById('category-delete-form').submit(); }"
                    />
                </div>
            </form>

            <form id="category-delete-form" method="POST" x-bind:action="categoryDeleteAction" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <div class="flex-1 min-h-0 overflow-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="text-left w-16">Image</th>
                            <th class="text-left">Catégorie</th>
                            <th class="text-left">Description</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="admin-table-body">
                        @forelse($categories as $cat)
                            <tr
                                class="admin-row-hover"
                                :class="selectedCategoryId === {{ $cat->id }} ? 'admin-row-selected' : ''"
                                @click="selectedCategoryId = {{ $cat->id }}"
                                @dblclick="window.location.href = '{{ route('categories.index', ['edit_category' => $cat->id]) }}'"
                            >
                                <td class="admin-table-cell py-2">
                                    @if($cat->imageUrl())
                                        <img src="{{ $cat->imageUrl() }}" alt="{{ $cat->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-600">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-400 text-xs">—</div>
                                    @endif
                                </td>
                                <td class="admin-table-cell py-2 font-medium">{{ $cat->name }}</td>
                                <td class="admin-table-cell-muted py-2 max-w-[12rem] truncate" title="{{ $cat->description }}">{{ $cat->description ?? '—' }}</td>
                                <td class="admin-table-cell py-2 text-center">
                                    @if($cat->is_active)
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Actif</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Inactif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="admin-table-cell py-6 text-center text-slate-500 dark:text-slate-400">Aucune catégorie</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="admin-panel flex flex-col"
            x-data="{
                selectedBrandId: {{ $brandForm?->id ?? 'null' }},
                brandDeleteAction: '',
            }"
            x-init="$watch('selectedBrandId', id => { brandDeleteAction = id ? '{{ url('brands') }}/' + id : '' })"
        >
            <div class="admin-panel-header">
                <h3 class="text-sm font-bold text-brand-900 dark:text-brand-200">
                    {{ $brandForm ? 'Modifier la marque' : 'Marques' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $brandForm ? $brandForm->name : 'Gérez les marques produits' }}
                </p>
            </div>

            <form
                id="brand-form"
                method="POST"
                action="{{ $brandForm ? route('brands.update', $brandForm) : route('brands.store') }}"
                enctype="multipart/form-data"
                class="admin-panel-form"
            >
                @csrf
                @if($brandForm) @method('PUT') @endif
                <div class="flex gap-3 items-start">
                    <div class="shrink-0 w-20 flex flex-col gap-1.5">
                        <div class="w-20 h-20 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center" id="brand-image-preview">
                            @if($brandForm?->imageUrl())
                                <img src="{{ $brandForm->imageUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <label for="brand_image" class="block w-full cursor-pointer rounded-md border border-brand-200 dark:border-brand-700 bg-brand-50 dark:bg-brand-900/40 px-1 py-1.5 text-center text-[10px] font-medium text-brand-700 dark:text-brand-300 hover:bg-brand-100 dark:hover:bg-brand-900/60 transition-colors">
                            Choisir fichier
                            <input type="file" id="brand_image" name="brand_image" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="sr-only" onchange="previewImage(event, 'brand-image-preview')">
                        </label>
                        @error('brand_image')<p class="text-red-500 text-[10px] text-center">{{ $message }}</p>@enderror
                        <p class="text-[9px] text-slate-400 text-center leading-tight">JPG, PNG, WebP · max. 5 Mo</p>
                    </div>
                    <div class="flex-1 min-w-0 space-y-2">
                        <div>
                            <label for="brand_name" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Nom marque *</label>
                            <input type="text" id="brand_name" name="name" value="{{ old('name', $brandForm->name ?? '') }}" required placeholder="Ex. Samsung" class="form-input w-full py-1.5">
                            @error('name')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="brand_description" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Description</label>
                            <input type="text" id="brand_description" name="description" value="{{ old('description', $brandForm->description ?? '') }}" placeholder="Description de la marque" class="form-input w-full py-1.5">
                            @error('description')<p class="text-red-500 text-xs mt-0.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="brand_is_active" class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">Actions</label>
                            <select id="brand_is_active" name="is_active" class="form-input w-full py-1.5">
                                <option value="1" @selected($brandActive)>Actif</option>
                                <option value="0" @selected(! $brandActive)>Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-2 notranslate" translate="no">
                    @if($brandForm)
                        <x-admin.action-btn href="{{ route('categories.index') }}" icon="cancel" label="Annuler" />
                        <x-admin.action-btn type="submit" icon="save" label="Valider" variant="success" />
                    @else
                        <x-admin.action-btn type="submit" icon="plus" label="Ajouter" variant="primary" />
                    @endif
                    <x-admin.action-btn
                        icon="edit"
                        label="Modifier"
                        x-bind:disabled="!selectedBrandId"
                        @click="selectedBrandId && (window.location.href = '{{ route('categories.index') }}?edit_brand=' + selectedBrandId)"
                    />
                    <x-admin.action-btn
                        icon="delete"
                        label="Supprimer"
                        variant="danger"
                        x-bind:disabled="!selectedBrandId"
                        @click="if (selectedBrandId && confirm('Supprimer cette marque ?')) { document.getElementById('brand-delete-form').submit(); }"
                    />
                </div>
            </form>

            <form id="brand-delete-form" method="POST" x-bind:action="brandDeleteAction" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <div class="flex-1 min-h-0 overflow-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="text-left w-16">Image</th>
                            <th class="text-left">Marque</th>
                            <th class="text-left">Description</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="admin-table-body">
                        @forelse($brands as $brand)
                            <tr
                                class="admin-row-hover"
                                :class="selectedBrandId === {{ $brand->id }} ? 'admin-row-selected' : ''"
                                @click="selectedBrandId = {{ $brand->id }}"
                                @dblclick="window.location.href = '{{ route('categories.index', ['edit_brand' => $brand->id]) }}'"
                            >
                                <td class="admin-table-cell py-2">
                                    @if($brand->imageUrl())
                                        <img src="{{ $brand->imageUrl() }}" alt="{{ $brand->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-600">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-400 text-xs">—</div>
                                    @endif
                                </td>
                                <td class="admin-table-cell py-2 font-medium">{{ $brand->name }}</td>
                                <td class="admin-table-cell-muted py-2 max-w-[12rem] truncate" title="{{ $brand->description }}">{{ $brand->description ?? '—' }}</td>
                                <td class="admin-table-cell py-2 text-center">
                                    @if($brand->is_active)
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">Actif</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Inactif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="admin-table-cell py-6 text-center text-slate-500 dark:text-slate-400">Aucune marque</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event, targetId) {
            const file = event.target.files[0];
            const preview = document.getElementById(targetId);
            if (!file || !preview) return;
            preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="" class="w-full h-full object-cover">`;
        }
    </script>
</x-admin-layout>
