<x-admin-layout title="Livreurs">
    <x-admin.list-page>
        <x-admin.data-table class="flex-1 min-h-0">
            @if($livreurs->hasPages())
                <x-slot:footer>{{ $livreurs->links() }}</x-slot:footer>
            @endif
            <thead>
                <tr>
                    <th class="text-left">Nom</th>
                    <th class="text-left">E-mail</th>
                    <th class="text-left">Téléphone</th>
                    <th class="text-center">Livraisons actives</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($livreurs as $livreur)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium">{{ $livreur->name }}</td>
                        <td class="px-5 py-3">{{ $livreur->email }}</td>
                        <td class="px-5 py-3">{{ $livreur->phone ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">{{ $livreur->active_deliveries_count }}</td>
                        <td class="px-5 py-3 text-center">{{ $livreur->is_active ? 'Actif' : 'Inactif' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Aucun livreur</td></tr>
                @endforelse
            </tbody>
        </x-admin.data-table>
    </x-admin.list-page>
</x-admin-layout>
