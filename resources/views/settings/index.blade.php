<x-admin-layout title="Fiche Société">
    <form method="POST" action="{{ route('settings.update') }}" class="max-w-2xl bg-white rounded-xl border p-6 shadow-sm space-y-6">
        @csrf @method('PUT')
        <div><h3 class="font-semibold mb-3">Informations entreprise</h3>
            <div class="space-y-3">
                <input type="text" name="company_name" value="{{ $settings['company_name'] }}" placeholder="Nom entreprise" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="email" name="company_email" value="{{ $settings['company_email'] }}" placeholder="Email" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="text" name="company_phone" value="{{ $settings['company_phone'] }}" placeholder="Téléphone" class="w-full rounded-lg border-slate-300 text-sm">
                <textarea name="company_address" rows="2" placeholder="Adresse" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['company_address'] }}</textarea>
            </div>
        </div>
        <div><h3 class="font-semibold mb-3">Paramètres commerciaux</h3>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="text-sm text-slate-500 dark:text-slate-400">Préfixe commandes</label><input type="text" name="order_prefix" value="{{ $settings['order_prefix'] }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="text-sm text-slate-500 dark:text-slate-400">Taux commission (%)</label><input type="number" step="0.1" name="commission_rate" value="{{ $settings['commission_rate'] }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="text-sm text-slate-500 dark:text-slate-400">Frais livraison</label><input type="number" step="0.01" name="delivery_fee" value="{{ $settings['delivery_fee'] }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
            </div>
        </div>
        <div><label class="text-sm text-slate-500 dark:text-slate-400">Pied de page facture</label><textarea name="invoice_footer" rows="2" class="w-full rounded-lg border-slate-300 text-sm">{{ $settings['invoice_footer'] }}</textarea></div>
        <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm">Enregistrer</button>
    </form>
</x-admin-layout>
