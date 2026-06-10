<x-admin-layout title="Commerciaux">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($commercials as $commercial)
            <a href="{{ route('commercials.show', $commercial) }}" class="bg-white rounded-xl border p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">{{ strtoupper(substr($commercial->name, 0, 1)) }}</div>
                    <div><div class="font-semibold">{{ $commercial->name }}</div><div class="text-xs text-slate-500">{{ $commercial->email }}</div></div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="bg-slate-50 rounded-lg p-2 text-center"><div class="text-slate-500">CA</div><div class="font-bold">{{ number_format($commercial->total_sales ?? 0, 0, ',', ' ') }} DH</div></div>
                    <div class="bg-slate-50 rounded-lg p-2 text-center"><div class="text-slate-500">Livrées</div><div class="font-bold">{{ $commercial->delivered_orders_count ?? 0 }}</div></div>
                </div>
            </a>
        @endforeach
    </div>
</x-admin-layout>
