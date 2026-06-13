<x-admin-layout title="Historique des mouvements">
    <x-admin.list-page>
        <x-slot:toolbar>
            <a href="{{ route('stock.index') }}" class="text-sm text-brand-600 inline-block">← Retour au stock</a>
        </x-slot:toolbar>

        <x-admin.data-table class="flex-1 min-h-0">
            @if($movements->hasPages())
                <x-slot:footer>{{ $movements->links() }}</x-slot:footer>
            @endif
            <thead><tr><th class="text-left">Produit</th><th class="text-left">Type</th><th class="text-center">Qté</th><th class="text-left">Utilisateur</th><th class="text-left">Date</th></tr></thead>
            <tbody class="divide-y">
                @foreach($movements as $m)
                    <tr><td class="px-5 py-3">{{ $m->product->name }}</td><td class="px-5 py-3">{{ $m->type->label() }}</td><td class="px-5 py-3 text-center">{{ $m->quantity_before }} → {{ $m->quantity_after }}</td><td class="px-5 py-3">{{ $m->user?->name ?? '-' }}</td><td class="px-5 py-3">{{ $m->created_at->format('d/m/Y H:i') }}</td></tr>
                @endforeach
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
