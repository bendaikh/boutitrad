@props(['order'])

<div class="admin-bon-info-grid">
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Date commande</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->created_at->format('d/m/Y') }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Réf bon</p>
        <p class="font-mono text-xs font-medium text-slate-900 dark:text-slate-100 break-all">{{ $order->reference }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">ID client</p>
        <p class="font-mono text-xs font-medium text-slate-900 dark:text-slate-100">{{ $order->client->formattedId() }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Nom client</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->client->name }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Commercial</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->commercial?->name ?? '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Ville livraison</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->client->deliveryCityName() ?: '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Adresse</p>
        <p class="text-slate-800 dark:text-slate-200 break-words">{{ $order->client->address ?: '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Téléphone</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->client->phone ?: '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Livreur</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->livreur?->name ?? '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Partenaire</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->deliveryPartner?->name ?? '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Réf livraison</p>
        <p class="font-mono text-xs font-medium text-slate-900 dark:text-slate-100 break-all">{{ $order->deliveryReference() ?? '—' }}</p>
        @if($order->deliveryPartner && $order->deliveryReference())
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $order->deliveryPartner->name }}</p>
        @endif
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Statut Cathedis</p>
        <x-admin.cathedis-status-badge :order="$order" />
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Mode paiement</p>
        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $order->payment_mode?->label() ?? '—' }}</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Montant payé</p>
        <p class="font-medium tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($order->amount_paid, 2, ',', ' ') }} DH</p>
    </div>
    <div class="admin-bon-info-cell">
        <p class="admin-order-form-label">Solde</p>
        <p @class([
            'font-semibold tabular-nums',
            $order->balanceDue() > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400',
        ])>{{ number_format($order->balanceDue(), 2, ',', ' ') }} DH</p>
    </div>
</div>
