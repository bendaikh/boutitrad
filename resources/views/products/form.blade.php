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
                <div class="col-span-1 xl:col-span-2">
                    <label for="city" class="admin-product-form-label">Ville</label>
                    <input type="text" id="city" name="city" value="{{ old('city', $product->city ?? '') }}" class="admin-product-form-input">
                    @error('city')<p class="text-red-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
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
