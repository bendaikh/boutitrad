<x-admin-layout title="{{ isset($product) ? 'Modifier produit' : 'Nouveau produit' }}">
    <form method="POST" action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}" class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf @if(isset($product)) @method('PUT') @endif
        <div><label class="block text-sm font-medium mb-1">Nom *</label><input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">SKU *</label><input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" required class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="block text-sm font-medium mb-1">Unité</label><input type="text" name="unit" value="{{ old('unit', $product->unit ?? 'unité') }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Catégorie</label><select name="category_id" class="w-full rounded-lg border-slate-300 text-sm"><option value="">-</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium mb-1">Marque</label><select name="brand_id" class="w-full rounded-lg border-slate-300 text-sm"><option value="">-</option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Prix achat</label><input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="block text-sm font-medium mb-1">Prix vente</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">Quantité</label><input type="number" name="quantity" value="{{ old('quantity', $product->quantity ?? 0) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
            <div><label class="block text-sm font-medium mb-1">Seuil alerte</label><input type="number" name="min_quantity" value="{{ old('min_quantity', $product->min_quantity ?? 5) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
        </div>
        <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ old('description', $product->description ?? '') }}</textarea></div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded text-brand-600"> Actif</label>
        <div class="flex gap-3"><button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm">Enregistrer</button><a href="{{ route('products.index') }}" class="px-5 py-2 bg-slate-100 rounded-lg text-sm">Annuler</a></div>
    </form>
</x-admin-layout>
