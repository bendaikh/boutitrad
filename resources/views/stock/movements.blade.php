<x-admin-layout title="Historique des mouvements">
    <a href="{{ route('stock.index') }}" class="text-sm text-brand-600 mb-4 inline-block">← Retour au stock</a>
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Produit</th><th class="px-5 py-3 text-left">Type</th><th class="px-5 py-3 text-center">Qté</th><th class="px-5 py-3 text-left">Utilisateur</th><th class="px-5 py-3 text-left">Date</th></tr></thead>
            <tbody class="divide-y">
                @foreach($movements as $m)
                    <tr><td class="px-5 py-3">{{ $m->product->name }}</td><td class="px-5 py-3">{{ $m->type->label() }}</td><td class="px-5 py-3 text-center">{{ $m->quantity_before }} → {{ $m->quantity_after }}</td><td class="px-5 py-3">{{ $m->user?->name ?? '-' }}</td><td class="px-5 py-3">{{ $m->created_at->format('d/m/Y H:i') }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @if($movements->hasPages())<div class="px-5 py-3 border-t">{{ $movements->links() }}</div>@endif
    </div>
</x-admin-layout>
