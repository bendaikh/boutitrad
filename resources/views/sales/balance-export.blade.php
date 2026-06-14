<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Balance ventes — BELDI-MALAKI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 32px auto; color: #1e293b; background: #f8fafc; }
        .sheet { background: #fff; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .kpi { border-radius: 8px; padding: 12px 14px; color: #fff; }
        .kpi-green { background: #059669; }
        .kpi-blue { background: #2563eb; }
        .kpi-red { background: #dc2626; }
        .kpi-label { font-size: 11px; text-transform: uppercase; font-weight: bold; opacity: 0.9; }
        .kpi-value { font-size: 20px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 8px; text-align: left; }
        th { background: #334155; color: #f8fafc; font-weight: bold; }
        td.right { text-align: right; }
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
        $exportQuery = http_build_query(array_filter(request()->only(['date_from', 'date_to', 'reference', 'client', 'ville']), fn ($v) => $v !== null && $v !== ''));
        $exportSuffix = $exportQuery ? '?'.$exportQuery : '';
        $periodLabel = ($dateFrom || $dateTo)
            ? trim(($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...').' — '.($dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...'))
            : 'Toutes dates';
    @endphp
    @unless($forPdf ?? false)
        <div class="no-print toolbar">
            <button type="button" onclick="window.print()" class="btn btn-dark">Imprimer</button>
            <a href="{{ route('sales.balance.export.pdf').$exportSuffix }}" class="btn btn-light">Exporter PDF</a>
            <a href="{{ route('sales.balance.export.excel').$exportSuffix }}" class="btn btn-success">Exporter Excel</a>
            <button type="button" onclick="window.close()" class="btn btn-light">Fermer</button>
        </div>
    @endunless

    <div class="sheet">
        <div class="header">
            <div>
                <div class="logo">BELDI-MALAKI</div>
                <p class="meta">Balance ventes — Période : {{ $periodLabel }}</p>
            </div>
            <p class="meta">{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="kpis">
            <div class="kpi kpi-green">
                <div class="kpi-label">Nombre de commandes</div>
                <div class="kpi-value">{{ number_format($stats['orders_count'], 0, ',', ' ') }}</div>
            </div>
            <div class="kpi kpi-blue">
                <div class="kpi-label">Montant total des commandes</div>
                <div class="kpi-value">{{ number_format($stats['orders_total'], 2, ',', ' ') }} DH</div>
            </div>
            <div class="kpi kpi-red">
                <div class="kpi-label">Solde des commandes</div>
                <div class="kpi-value">{{ number_format($stats['orders_balance'], 2, ',', ' ') }} DH</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>N° bon</th>
                    <th>ID client</th>
                    <th>Nom client</th>
                    <th>Ville</th>
                    <th>Désignation</th>
                    <th>Qté</th>
                    <th>Prix U</th>
                    <th>Mnt total</th>
                    <th>Mnt payé</th>
                    <th>Mode paiement</th>
                    <th>Solde</th>
                    <th>Commercial</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $order = $item->order;
                        $client = $order->client;
                    @endphp
                    <tr>
                        <td class="center">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td>{{ $order->reference }}</td>
                        <td>{{ $client->formattedId() }}</td>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->city ?? '—' }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                        <td class="right">{{ number_format($item->total, 2, ',', ' ') }}</td>
                        <td class="right">{{ number_format($order->paidAmount(), 2, ',', ' ') }}</td>
                        <td>{{ $order->payment_mode?->label() ?? '—' }}</td>
                        <td class="right">{{ number_format($order->balanceDue(), 2, ',', ' ') }}</td>
                        <td>{{ $order->commercial?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" style="text-align: center; color: #64748b;">Aucune ligne</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
