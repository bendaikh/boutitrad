<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>État du stock — BELDI-MALAKI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 32px auto; color: #1e293b; background: #f8fafc; }
        .sheet { background: #fff; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; background: #f8fafc; }
        .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; }
        .kpi-value { font-size: 18px; font-weight: bold; color: #0f172a; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        th { background: #334155; color: #f8fafc; font-weight: bold; }
        td.center { text-align: center; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-dark { background: #00332B; color: #fff; }
        .btn-light { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .btn-success { background: #059669; color: #fff; }
        .no-print { margin-bottom: 0; }
        @media print {
            body { margin: 0; background: #fff; }
            .sheet { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $exportQuery = http_build_query(request()->only(['category_id', 'status', 'etat', 'search']));
    @endphp
    @unless($forPdf ?? false)
        <div class="no-print toolbar">
            <button type="button" onclick="window.print()" class="btn btn-dark">Imprimer</button>
            <a href="{{ route('stock.export.pdf').($exportQuery ? '?'.$exportQuery : '') }}" class="btn btn-light">Exporter PDF</a>
            <a href="{{ route('stock.export.excel').($exportQuery ? '?'.$exportQuery : '') }}" class="btn btn-success">Exporter Excel</a>
            <button type="button" onclick="window.close()" class="btn btn-light">Fermer</button>
        </div>
    @endunless

    <div class="sheet">
        <div class="header">
            <div>
                <div class="logo">BELDI-MALAKI</div>
                <p class="meta">Gestion du stock</p>
            </div>
            <p class="meta">{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="kpis">
            <div class="kpi">
                <div class="kpi-label">Stock vendu</div>
                <div class="kpi-value">{{ number_format($stats['soldStockQty'], 0, ',', ' ') }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Stock réel</div>
                <div class="kpi-value">{{ number_format($stats['realStockQty'], 0, ',', ' ') }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Stock faible</div>
                <div class="kpi-value">{{ number_format($stats['lowStockQty'], 0, ',', ' ') }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Stock en rupture</div>
                <div class="kpi-value">{{ number_format($stats['outOfStockQty'], 0, ',', ' ') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Réf prod</th>
                    <th>Désignation prod</th>
                    <th>Catégorie prod</th>
                    <th>Quantité prod</th>
                    <th>Statut prod</th>
                    <th>État prod</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['sku'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['category'] }}</td>
                        <td class="center">{{ $row['quantity'] }}</td>
                        <td class="center">{{ $row['status'] }}</td>
                        <td class="center">{{ $row['etat'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Aucun produit</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
