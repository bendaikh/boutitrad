<x-admin-layout title="Produits">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <form method="GET" class="flex gap-2 flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="rounded-lg border-slate-300 text-sm">
            <select name="category_id" class="rounded-lg border-slate-300 text-sm">
                <option value="">Toutes catégories</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>@endforeach
            </select>
            <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="low_stock" value="1" @checked(request('low_stock'))> Rupture</label>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm">Filtrer</button>
        </form>
        <a href="{{ route('products.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Nouveau produit</a>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr>
                <th class="px-5 py-3 text-left">Produit</th><th class="px-5 py-3 text-left">SKU</th><th class="px-5 py-3 text-left">Catégorie</th>
                <th class="px-5 py-3 text-right">Prix vente</th><th class="px-5 py-3 text-center">Stock</th><th class="px-5 py-3 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr class="hover:bg-slate-50 {{ $product->isLowStock() ? 'bg-amber-50/50' : '' }}">
                        <td class="px-5 py-3"><a href="{{ route('products.show', $product) }}" class="text-indigo-600 font-medium">{{ $product->name }}</a></td>
                        <td class="px-5 py-3 text-slate-500">{{ $product->sku }}</td>
                        <td class="px-5 py-3">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">{{ number_format($product->sale_price, 2, ',', ' ') }} DH</td>
                        <td class="px-5 py-3 text-center"><span class="{{ $product->isLowStock() ? 'text-red-600 font-semibold' : '' }}">{{ $product->quantity }}</span></td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('products.edit', $product) }}" class="text-indigo-600">Modifier</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun produit</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())<div class="px-5 py-3 border-t">{{ $products->links() }}</div>@endif
    </div>
</x-admin-layout>
