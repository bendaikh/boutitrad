@php
    $product = $product ?? null;
    $isEdit = $product !== null;
    $formActive = $formActive ?? true;
@endphp

<form
    id="product-form"
    method="POST"
    action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}"
    class="admin-form-shell"
>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="min_quantity" value="{{ old('min_quantity', $product->min_quantity ?? 5) }}">
    <input type="hidden" name="unit" value="{{ old('unit', $product->unit ?? 'unité') }}">
    <input type="hidden" name="is_active" value="1">

    <div class="admin-product-form-toolbar {{ $formActive ? '' : 'bg-slate-100 dark:bg-slate-800/60' }}">
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
        </div>
        <div class="flex flex-wrap items-center gap-2 notranslate shrink-0" translate="no">
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

    <div class="relative transition-opacity" :class="!formActive && 'opacity-55'">
        <div
            x-show="!formActive"
            x-cloak
            class="admin-form-overlay"
        ></div>

        <fieldset x-bind:disabled="!formActive" class="border-0 p-0 m-0 min-w-0">
            <div class="px-3 py-2.5 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-12 gap-x-2 gap-y-2">
                <div class="col-span-1 xl:col-span-2">
                    <label for="sku" class="admin-product-form-label">Réf produit *</label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku ?? '') }}" @if($formActive) required @endif placeholder="SAM-S24-001" class="admin-product-form-input font-mono text-xs">
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
