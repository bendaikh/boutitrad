<x-admin-layout title="Gestion du Stock">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-admin.stat-card label="Valorisation stock" :value="number_format($totalValue, 0, ',', ' ').' DH'" color="indigo" />
        <x-admin.stat-card label="Alertes rupture" :value="$lowStockCount" color="amber" />
        <a href="{{ route('stock.movements') }}" class="flex items-center justify-center bg-white rounded-xl border p-5 text-brand-600 font-medium hover:bg-brand-50">Voir historique →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Produit</th><th class="px-5 py-3 text-center">Stock</th><th class="px-5 py-3 text-center">Seuil</th><th class="px-5 py-3 text-right">Valeur</th></tr></thead>
                <tbody class="divide-y">
                    @foreach($products as $p)
                        <tr class="{{ $p->isLowStock() ? 'bg-amber-50' : '' }}">
                            <td class="px-5 py-3 font-medium">{{ $p->name }}</td>
                            <td class="px-5 py-3 text-center {{ $p->isLowStock() ? 'text-red-600 font-bold' : '' }}">{{ $p->quantity }}</td>
                            <td class="px-5 py-3 text-center">{{ $p->min_quantity }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($p->stockValue(), 2, ',', ' ') }} DH</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($products->hasPages())<div class="px-5 py-3 border-t">{{ $products->links() }}</div>@endif
        </div>

        <form method="POST" action="{{ route('stock.adjust') }}" class="bg-white rounded-xl border p-5 shadow-sm space-y-3 h-fit">
            @csrf
            <h3 class="font-semibold">Mouvement de stock</h3>
            <select name="product_id" required class="w-full rounded-lg border-slate-300 text-sm"><option value="">Produit...</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <select name="type" required class="w-full rounded-lg border-slate-300 text-sm">
                <option value="entree">Entrée</option><option value="sortie">Sortie</option><option value="ajustement">Ajustement</option><option value="inventaire">Inventaire</option>
            </select>
            <input type="number" name="quantity" min="0" required placeholder="Quantité" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="text" name="reference" placeholder="Référence" class="w-full rounded-lg border-slate-300 text-sm">
            <textarea name="notes" rows="2" placeholder="Notes" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
            <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm">Enregistrer</button>
        </form>
    </div>
</x-admin-layout>
