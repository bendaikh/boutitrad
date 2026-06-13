<x-admin-layout title="Balance">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.stat-card compact label="Ventes réalisées" :value="number_format($totalSales, 2, ',', ' ').' DH'" color="emerald" />
        <x-admin.stat-card compact label="Encours commandes" :value="number_format($pendingAmount, 2, ',', ' ').' DH'" color="brand" />
        <x-admin.stat-card compact label="Solde clients" :value="number_format($clientsBalance, 2, ',', ' ').' DH'" color="blue" />
        <x-admin.stat-card compact label="Dettes clients" :value="number_format(abs($clientsDebt), 2, ',', ' ').' DH'" color="rose" />
    </div>
</x-admin-layout>
