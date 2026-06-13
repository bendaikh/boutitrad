<x-admin-layout title="Produits">
    @php
        $formProduct = $editingProduct ?? null;
        $formActive = ($formActive ?? false) || $errors->any();
        $initialSelectedId = $editingProduct?->id;
        $productsPath = parse_url(route('products.index'), PHP_URL_PATH) ?: '/products';
        $annulerUrl = $productsPath;
        $editBaseUrl = $productsPath;
    @endphp
    <div
        x-data="{
            formActive: {{ $formActive ? 'true' : 'false' }},
            selectedId: {{ $initialSelectedId ? $initialSelectedId : 'null' }},
            printUrl: '{{ $productsPath }}',
            editUrl: '{{ $editBaseUrl }}',
            annulerUrl: '{{ $annulerUrl }}',
            editingProductId: {{ $editingProduct?->id ?? 'null' }},
            async deleteProduct() {
                if (!this.selectedId || !confirm('Supprimer ce produit ?')) return;
                const row = document.querySelector(`[data-product-id='${this.selectedId}']`);
                const id = this.selectedId;
                const form = document.getElementById('product-delete-form');
                if (!form) {
                    alert('Formulaire de suppression introuvable.');
                    return;
                }
                form.action = `${this.editUrl}/${id}`;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('delete_failed');
                    if (row) row.remove();
                    this.selectedId = null;
                    if (this.editingProductId === id) {
                        window.location.href = this.annulerUrl;
                    }
                } catch (e) {
                    alert('Impossible de supprimer ce produit.');
                }
            },
        }"
    >
        <x-admin.list-page>
            <form id="product-delete-form" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <div class="shrink-0">
                @include('products.form', [
                    'product' => $formProduct,
                    'formActive' => $formActive,
                    'categories' => $categories,
                    'brands' => $brands,
                ])
            </div>

            <x-admin.data-table min-width="1180px" class="flex-1 min-h-0">
                @if($products->hasPages())
                    <x-slot:footer>{{ $products->links() }}</x-slot:footer>
                @endif
                <thead>
                    <tr>
                        <th class="text-left">Réf produit</th>
                        <th class="text-left">Désignation produit</th>
                        <th class="text-left">Catégorie produit</th>
                        <th class="text-left">Frns produit</th>
                        <th class="text-left">Ville produit</th>
                        <th class="text-right">Prix d'achat</th>
                        <th class="text-right">Prix de vente</th>
                        <th class="text-center">Statut</th>
                    </tr>
                </thead>
                <tbody class="admin-table-body">
                    @forelse($products as $product)
                        @php
                            $statusClass = match ($product->stockStatus()) {
                                'dispo' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
                                'faible' => 'bg-yellow-300 text-yellow-900 dark:bg-yellow-900/50 dark:text-yellow-200 font-bold',
                                'rupture' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-semibold',
                            };
                        @endphp
                        <tr
                            data-product-id="{{ $product->id }}"
                            class="admin-row-hover"
                            :class="selectedId === {{ $product->id }} ? 'admin-row-selected' : ''"
                            @click="selectedId = {{ $product->id }}"
                            @dblclick="window.location.href = editUrl + (editUrl.includes('?') ? '&' : '?') + 'edit={{ $product->id }}'"
                        >
                            <td class="admin-table-cell-muted font-mono text-xs">{{ $product->sku }}</td>
                            <td class="admin-table-cell font-medium">{{ $product->name }}</td>
                            <td class="admin-table-cell">{{ $product->category?->name ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $product->supplier ?? '—' }}</td>
                            <td class="admin-table-cell">{{ $product->city ?? '—' }}</td>
                            <td class="admin-table-cell text-right">{{ number_format($product->purchase_price, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell text-right">{{ number_format($product->sale_price, 2, ',', ' ') }} DH</td>
                            <td class="admin-table-cell text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $product->stockStatusLabel() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-table-cell text-center text-slate-500 dark:text-slate-400">Aucun produit</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.data-table>
        </x-admin.list-page>
    </div>
</x-admin-layout>
