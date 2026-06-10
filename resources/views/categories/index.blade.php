<x-admin-layout title="Catégories & Marques">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Catégories</h3>
            <form method="POST" action="{{ route('categories.store') }}" class="flex gap-2 mb-4">@csrf<input type="text" name="name" required placeholder="Nouvelle catégorie" class="flex-1 rounded-lg border-slate-300 text-sm"><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Ajouter</button></form>
            <ul class="divide-y text-sm">@foreach($categories as $cat)<li class="py-2 flex justify-between"><span>{{ $cat->name }}</span><span class="text-slate-500">{{ $cat->products_count }} produits</span></li>@endforeach</ul>
        </div>
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Marques</h3>
            <form method="POST" action="{{ route('brands.store') }}" class="flex gap-2 mb-4">@csrf<input type="text" name="name" required placeholder="Nouvelle marque" class="flex-1 rounded-lg border-slate-300 text-sm"><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Ajouter</button></form>
            <ul class="divide-y text-sm">@foreach($brands as $brand)<li class="py-2 flex justify-between"><span>{{ $brand->name }}</span><span class="text-slate-500">{{ $brand->products_count }} produits</span></li>@endforeach</ul>
        </div>
    </div>
</x-admin-layout>
