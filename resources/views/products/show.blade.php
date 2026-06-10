<x-admin-layout title="{{ $product->name }}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border p-5 shadow-sm space-y-2 text-sm">
            <h2 class="font-semibold text-lg mb-3">{{ $product->name }}</h2>
            <p><span class="text-slate-500">SKU:</span> {{ $product->sku }}</p>
            <p><span class="text-slate-500">Catégorie:</span> {{ $product->category?->name ?? '-' }}</p>
            <p><span class="text-slate-500">Marque:</span> {{ $product->brand?->name ?? '-' }}</p>
            <p><span class="text-slate-500">Stock:</span> <strong class="{{ $product->isLowStock() ? 'text-red-600' : '' }}">{{ $product->quantity }} {{ $product->unit }}</strong></p>
            <p><span class="text-slate-500">Valeur stock:</span> {{ number_format($product->stockValue(), 2, ',', ' ') }} DH</p>
            <a href="{{ route('products.edit', $product) }}" class="inline-block mt-3 text-indigo-600 text-sm">Modifier</a>
        </div>
        <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b font-semibold">Mouvements récents</div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr><th class="px-4 py-2 text-left">Type</th><th class="px-4 py-2 text-center">Qté</th><th class="px-4 py-2 text-center">Avant→Après</th><th class="px-4 py-2 text-left">Date</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($product->stockMovements as $m)
                        <tr><td class="px-4 py-2">{{ $m->type->label() }}</td><td class="px-4 py-2 text-center">{{ $m->quantity }}</td><td class="px-4 py-2 text-center">{{ $m->quantity_before }} → {{ $m->quantity_after }}</td><td class="px-4 py-2">{{ $m->created_at->format('d/m/Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucun mouvement</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
