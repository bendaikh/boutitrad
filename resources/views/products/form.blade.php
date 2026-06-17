@php
    $product = $product ?? null;
    $isEdit = $product !== null;
    $formActive = $formActive ?? true;
@endphp

<form
    id="product-form"
    method="POST"
    action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}"
    enctype="multipart/form-data"
    class="admin-form-shell"
>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="min_quantity" value="{{ old('min_quantity', $product->min_quantity ?? 5) }}">
    <input type="hidden" name="unit" value="{{ old('unit', $product->unit ?? 'unité') }}">
    <input type="hidden" name="is_active" value="1">

    <div class="admin-product-form-toolbar {{ $formActive ? '' : 'bg-slate-100 dark:bg-slate-800/60' }}">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <div
                id="product-image-preview"
                class="w-11 h-11 shrink-0 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center"
                title="Aperçu image produit"
            >
                @if($product?->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="" class="w-full h-full object-cover">
                @else
                    <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                @endif
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-bold leading-tight {{ $formActive ? 'text-brand-900 dark:text-brand-200' : 'text-slate-500 dark:text-slate-400' }}">
                    @if(! $formActive)
                        Fiche produit
                    @elseif($isEdit)
                        {{ $product->sku }} — {{ $product->name }}
                    @else
                        Nouveau produit
                    @endif
                </h3>
                @if($isEdit && $product->image)
                    <label class="inline-flex items-center gap-1.5 mt-1 text-[10px] text-slate-500 dark:text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('remove_image'))>
                        Supprimer l'image
                    </label>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 notranslate shrink-0" translate="no">
            <input
                type="file"
                x-ref="productImageInput"
                id="product_image"
                name="product_image"
                accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                class="sr-only"
                onchange="previewProductImage(event)"
            >
            <x-admin.action-btn
                type="button"
                icon="image"
                label="Ajouter image"
                variant="info"
                x-bind:disabled="!formActive"
                @click="$refs.productImageInput.click()"
            />
            <x-admin.action-btn
                icon="cancel"
                label="Annuler"
                x-bind:disabled="!formActive"
                @click="window.location.href = annulerUrl"
            />
            <x-admin.action-btn
                icon="plus"
                label="Nouveau produit"
                variant="primary"
                @click="window.location.href = '{{ route('products.index', ['new' => 1]) }}'"
            />
        </div>
    </div>
    @error('product_image')<p class="px-3 py-1 text-red-500 text-[10px] bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900/40">{{ $message }}</p>@enderror

    <div class="relative transition-opacity" :class="!formActive && 'opacity-55'">
        <div
            x-show="!formActive"
            x-cloak
            class="admin-form-overlay"
        ></div>

        <fieldset x-bind:disabled="!formActive" class="border-0 p-0 m-0 min-w-0">
            <div class="px-3 py-2.5 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-12 gap-x-2 gap-y-2">
                <div class="col-span-1 xl:col-span-2">
                    <label for="sku" class="admin-product-form-label">Réf produit</label>
                    @if($isEdit)
                        <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" readonly class="admin-product-form-readonly font-mono text-xs">
                    @else
                        <input type="text" id="sku" value="{{ $previewSku }}" readonly class="admin-product-form-readonly font-mono text-xs">
                        <p class="text-[10px] text-slate-500 mt-0.5">Attribuée automatiquement à l'enregistrement</p>
                    @endif
                    @error('sku')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-2">
                    <label for="barcode" class="admin-product-form-label">Barre code</label>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="EAN / UPC" class="admin-product-form-input font-mono text-xs">
                    @error('barcode')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-2 md:col-span-4 xl:col-span-3">
                    <label for="name" class="admin-product-form-label">Désignation produit *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name ?? '') }}" @if($formActive) required @endif class="admin-product-form-input">
                    @error('name')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-2 md:col-span-4 xl:col-span-3">
                    <label for="description" class="admin-product-form-label">Description produit</label>
                    <input type="text" id="description" name="description" value="{{ old('description', $product->description ?? '') }}" class="admin-product-form-input">
                    @error('description')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-2">
                    <label for="supplier" class="admin-product-form-label">Nom fournisseur</label>
                    <input type="text" id="supplier" name="supplier" value="{{ old('supplier', $product->supplier ?? '') }}" class="admin-product-form-input">
                    @error('supplier')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-2 relative" x-data="productCityPicker()" @click.outside="closeCityDropdown()" :class="cityOpen && 'z-[150]'">
                    <label for="product_city_search" class="admin-product-form-label">Ville</label>
                    <input type="hidden" name="city_id" :value="cityId">
                    <input type="hidden" name="city" :value="cityQuery">
                    <input
                        type="text"
                        id="product_city_search"
                        x-ref="cityInput"
                        x-model="cityQuery"
                        @focus="openCityDropdown($event)"
                        @click="openCityDropdown($event)"
                        @input="openCityDropdown(); onCityQueryInput()"
                        @keydown.escape.prevent="closeCityDropdown()"
                        @keydown.arrow-down.prevent="openCityDropdown()"
                        autocomplete="off"
                        placeholder="Ex : Casa, Rabat, Marrakech…"
                        class="admin-product-form-input"
                    >
                    <div
                        x-show="cityOpen"
                        x-cloak
                        x-bind:style="cityDropdownStyle"
                        class="admin-city-search-dropdown"
                    >
                        <p
                            x-show="cityQuery.trim().length < 2"
                            class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400"
                            x-text="cityResultsHint"
                        ></p>
                        <p
                            x-show="cityQuery.trim().length >= 2 && cityMatches.length === 0"
                            class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400"
                        >Aucune ville trouvée — synchronisez les villes Cathedis</p>
                        <template x-for="city in filteredCities" :key="city.id">
                            <button
                                type="button"
                                class="admin-city-search-option"
                                @mousedown.prevent="selectCity(city)"
                                x-text="city.name"
                            ></button>
                        </template>
                        <p
                            x-show="cityQuery.trim().length >= 2 && cityMatches.length > 0"
                            class="admin-city-search-hint"
                            x-text="cityResultsHint"
                        ></p>
                    </div>
                    @error('city')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    @error('city_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-2">
                    <label for="category_id" class="admin-product-form-label">Catégorie produit</label>
                    <select id="category_id" name="category_id" class="admin-product-form-input">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-2">
                    <label for="brand_id" class="admin-product-form-label">Marque produit</label>
                    <select id="brand_id" name="brand_id" class="admin-product-form-input">
                        <option value="">—</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-1">
                    <label for="quantity" class="admin-product-form-label">Stock initial</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="{{ old('quantity', $product->quantity ?? 0) }}" class="admin-product-form-input text-right tabular-nums">
                    @error('quantity')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-1">
                    <label for="purchase_price" class="admin-product-form-label">Prix achat</label>
                    <div class="relative">
                        <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="admin-product-form-input text-right tabular-nums pr-8">
                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400 pointer-events-none">DH</span>
                    </div>
                    @error('purchase_price')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1 xl:col-span-1">
                    <label for="sale_price" class="admin-product-form-label">Prix de vente</label>
                    <div class="relative">
                        <input type="number" id="sale_price" name="sale_price" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? 0) }}" class="admin-product-form-input text-right tabular-nums pr-8">
                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400 pointer-events-none">DH</span>
                    </div>
                    @error('sale_price')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>
    </div>

    @if($errors->any())
        <div class="px-3 py-2 text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border-t border-red-100 dark:border-red-800">
            Veuillez corriger les champs en erreur avant de valider.
        </div>
    @endif

    <div class="admin-product-form-actions notranslate" translate="no">
        @include('products.partials.form-actions')
    </div>
</form>

@push('scripts')
<script>
    function productCityPicker() {
        const cities = @json($citiesData ?? []);

        return {
            cityId: @json($initialCityId ? (string) $initialCityId : ''),
            cityQuery: @json($initialCityName ?? ''),
            cityOpen: false,
            cityDropdownStyle: '',
            get cityMatches() {
                const q = this.normalizeCitySearch(this.cityQuery.trim());
                if (q.length < 2) {
                    return [];
                }
                return cities.filter(city => this.normalizeCitySearch(city.name).includes(q));
            },
            get filteredCities() {
                const q = this.cityQuery.trim();
                if (q.length < 2) {
                    return cities.slice(0, 30);
                }
                return this.cityMatches.slice(0, 30);
            },
            get cityResultsHint() {
                const total = cities.length;
                const q = this.cityQuery.trim();
                if (q.length < 2) {
                    return total
                        ? `Recherche parmi ${total} villes — tapez au moins 2 lettres`
                        : 'Aucune ville — synchronisez Cathedis dans Livraison > Partenaires';
                }
                const matches = this.cityMatches.length;
                if (matches === 0) {
                    return '';
                }
                return matches === 1 ? '1 ville trouvée' : `${matches} villes trouvées`;
            },
            normalizeCitySearch(value) {
                return String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            },
            init() {
                this.syncCityQueryFromId();
                this.bindCityDropdownListeners();
            },
            bindCityDropdownListeners() {
                const reposition = () => {
                    if (this.cityOpen) {
                        this.updateCityDropdownPosition();
                    }
                };
                window.addEventListener('scroll', reposition, true);
                window.addEventListener('resize', reposition);
            },
            updateCityDropdownPosition() {
                const input = this.$refs.cityInput;
                if (! input) {
                    return;
                }
                const rect = input.getBoundingClientRect();
                const maxHeight = Math.max(240, Math.min(window.innerHeight * 0.7, 448, window.innerHeight - rect.bottom - 12));
                this.cityDropdownStyle = `top:${rect.bottom + 4}px;left:${rect.left}px;width:${rect.width}px;max-height:${maxHeight}px;`;
            },
            openCityDropdown(event = null) {
                this.cityOpen = true;
                this.$nextTick(() => this.updateCityDropdownPosition());
                if (event?.target?.select && this.cityQuery) {
                    event.target.select();
                }
            },
            closeCityDropdown() {
                this.cityOpen = false;
            },
            syncCityQueryFromId() {
                if (! this.cityId) {
                    return;
                }
                const city = cities.find(c => String(c.id) === String(this.cityId));
                if (city) {
                    this.cityQuery = city.name;
                }
            },
            selectCity(city) {
                this.cityId = String(city.id);
                this.cityQuery = city.name;
                this.closeCityDropdown();
            },
            onCityQueryInput() {
                const exact = cities.find(c => this.normalizeCitySearch(c.name) === this.normalizeCitySearch(this.cityQuery));
                if (exact) {
                    this.cityId = String(exact.id);
                    return;
                }
                this.cityId = '';
            },
        };
    }

    function previewProductImage(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('product-image-preview');
        if (!file || !preview) {
            return;
        }

        preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="" class="w-full h-full object-cover">`;

        const removeCheckbox = document.querySelector('input[name="remove_image"]');
        if (removeCheckbox) {
            removeCheckbox.checked = false;
        }
    }
</script>
@endpush
