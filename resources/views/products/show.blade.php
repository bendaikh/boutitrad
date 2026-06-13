<x-admin-layout title="{{ $product->name }}">
    <x-admin.list-page>
        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border p-5 shadow-sm space-y-2 text-sm shrink-0 overflow-y-auto">
                <h2 class="font-semibold text-lg mb-3">{{ $product->name }}</h2>
                <p><span class="text-slate-500 dark:text-slate-400">SKU:</span> {{ $product->sku }}</p>
                <p><span class="text-slate-500 dark:text-slate-400">Catégorie:</span> {{ $product->category?->name ?? '-' }}</p>
                <p><span class="text-slate-500 dark:text-slate-400">Marque:</span> {{ $product->brand?->name ?? '-' }}</p>
                <p><span class="text-slate-500 dark:text-slate-400">Stock:</span> <strong class="{{ $product->isLowStock() ? 'text-red-600' : '' }}">{{ $product->quantity }} {{ $product->unit }}</strong></p>
                <p><span class="text-slate-500 dark:text-slate-400">Valeur stock:</span> {{ number_format($product->stockValue(), 2, ',', ' ') }} DH</p>
                <a href="{{ route('products.edit', $product) }}" class="inline-block mt-3 text-brand-600 text-sm">Modifier</a>
            </div>
            <x-admin.data-table class="lg:col-span-2 flex-1 min-h-0">
                <x-slot:header>Mouvements récents</x-slot:header>
                <thead><tr><th class="text-left">Type</th><th class="text-center">Qté</th><th class="text-center">Avant→Après</th><th class="text-left">Date</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($product->stockMovements as $m)
                        <tr><td class="px-4 py-2">{{ $m->type->label() }}</td><td class="px-4 py-2 text-center">{{ $m->quantity }}</td><td class="px-4 py-2 text-center">{{ $m->quantity_before }} → {{ $m->quantity_after }}</td><td class="px-4 py-2">{{ $m->created_at->format('d/m/Y') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">Aucun mouvement</td></tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </div>
    </x-admin.list-page>
</x-admin-layout>
