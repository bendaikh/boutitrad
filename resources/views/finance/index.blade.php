<x-admin-layout title="Gestion Financière">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stat-card label="Recettes" :value="number_format($revenue, 0, ',', ' ').' DH'" color="emerald" />
        <x-admin.stat-card label="Dépenses" :value="number_format($expenses, 0, ',', ' ').' DH'" color="rose" />
        <x-admin.stat-card label="Bénéfice" :value="number_format($netProfit, 0, ',', ' ').' DH'" color="indigo" />
        <x-admin.stat-card label="Trésorerie" :value="number_format($treasury, 0, ',', ' ').' DH'" color="cyan" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Nouvelle dépense</h3>
            <form method="POST" action="{{ route('finance.expenses.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="title" required placeholder="Titre" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="number" step="0.01" name="amount" required placeholder="Montant" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="text" name="category" placeholder="Catégorie" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 text-sm">
                <button type="submit" class="w-full py-2 bg-rose-600 text-white rounded-lg text-sm">Enregistrer dépense</button>
            </form>
        </div>
        <div class="bg-white rounded-xl border p-5 shadow-sm">
            <h3 class="font-semibold mb-4">Opération de caisse</h3>
            <form method="POST" action="{{ route('finance.transactions.store') }}" class="space-y-3">
                @csrf
                <select name="type" class="w-full rounded-lg border-slate-300 text-sm"><option value="in">Encaissement</option><option value="out">Décaissement</option></select>
                <input type="number" step="0.01" name="amount" required placeholder="Montant" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="text" name="description" required placeholder="Description" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border-slate-300 text-sm">
                <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg text-sm">Enregistrer</button>
            </form>
        </div>
    </div>
</x-admin-layout>
