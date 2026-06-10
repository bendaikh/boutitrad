<x-admin-layout title="Nouvelle commande">
    <form method="POST" action="{{ route('orders.store') }}" class="max-w-4xl space-y-6" x-data="orderForm()">
        @csrf
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h2 class="font-semibold text-slate-800">Informations</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Client *</label>
                    <select name="client_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Sélectionner...</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Commercial</label>
                    <select name="commercial_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Auto</option>
                        @foreach($commercials as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Livreur</label>
                    <select name="livreur_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Non assigné</option>
                        @foreach($livreurs as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Remise (DH)</label>
                    <input type="number" step="0.01" name="discount" value="0" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-800">Produits</h2>
                <button type="button" @click="addItem()" class="text-sm text-indigo-600 hover:underline">+ Ajouter produit</button>
            </div>
            <template x-for="(item, index) in items" :key="index">
                <div class="grid grid-cols-12 gap-3 mb-3 items-end">
                    <div class="col-span-8">
                        <select :name="'items['+index+'][product_id]'" x-model="item.product_id" required class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Produit...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->quantity }} en stock) - {{ number_format($p->sale_price, 2) }} DH</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" min="1" required class="w-full rounded-lg border-slate-300 text-sm" placeholder="Qté">
                    </div>
                    <div class="col-span-1">
                        <button type="button" @click="removeItem(index)" class="p-2 text-red-500 hover:bg-red-50 rounded">&times;</button>
                    </div>
                </div>
            </template>
        </div>

        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Créer la commande</button>
    </form>

    @push('scripts')
    <script>
    function orderForm() {
        return {
            items: [{ product_id: '', quantity: 1 }],
            addItem() { this.items.push({ product_id: '', quantity: 1 }); },
            removeItem(i) { if (this.items.length > 1) this.items.splice(i, 1); }
        }
    }
    </script>
    @endpush
</x-admin-layout>
