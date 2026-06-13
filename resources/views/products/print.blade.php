<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Produit {{ $product->sku }} — {{ $product->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { width: 35%; background: #f1f5f9; color: #1e293b; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-dispo { background: #d1fae5; color: #065f46; }
        .badge-faible { background: #fde047; color: #713f12; }
        .badge-rupture { background: #fee2e2; color: #b91c1c; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #00332B; color: white; border: none; border-radius: 6px; cursor: pointer;">Imprimer</button>
    </div>

    <div class="header">
        <div>
            <div class="logo">BELDI-MALAKI</div>
            <p style="margin: 8px 0 0; color: #64748b;">Fiche produit</p>
            <p style="margin: 4px 0 0; font-weight: bold;">{{ $product->sku }}</p>
        </div>
        <p style="color: #64748b; font-size: 13px;">{{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <tr><th>Réf produit</th><td>{{ $product->sku }}</td></tr>
        <tr><th>Barre code</th><td>{{ $product->barcode ?? '—' }}</td></tr>
        <tr><th>Désignation produit</th><td>{{ $product->name }}</td></tr>
        <tr><th>Catégorie produit</th><td>{{ $product->category?->name ?? '—' }}</td></tr>
        <tr><th>Frns produit</th><td>{{ $product->supplier ?? '—' }}</td></tr>
        <tr><th>Ville produit</th><td>{{ $product->city ?? '—' }}</td></tr>
        <tr>
            <th>Statut</th>
            <td>
                @php
                    $badgeClass = match ($product->stockStatus()) {
                        'dispo' => 'badge-dispo',
                        'faible' => 'badge-faible',
                        'rupture' => 'badge-rupture',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $product->stockStatusLabel() }}</span>
                <span style="color: #64748b; font-size: 13px;">({{ $product->quantity }} {{ $product->unit }})</span>
            </td>
        </tr>
        <tr><th>Prix d'achat</th><td>{{ number_format($product->purchase_price, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Prix de vente</th><td>{{ number_format($product->sale_price, 2, ',', ' ') }} DH</td></tr>
        <tr><th>Marque</th><td>{{ $product->brand?->name ?? '—' }}</td></tr>
        @if($product->description)
            <tr><th>Description</th><td>{{ $product->description }}</td></tr>
        @endif
    </table>
</body>
</html>
