<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des commerciaux — BELDI-MALAKI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 32px auto; color: #1e293b; background: #f8fafc; }
        .sheet { background: #fff; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        th { background: #334155; color: #f8fafc; font-weight: bold; }
        td.right { text-align: right; }
        td.center { text-align: center; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-dark { background: #00332B; color: #fff; }
        .btn-success { background: #059669; color: #fff; }
        .btn-light { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .no-print { margin-bottom: 0; }
        @media print {
            body { margin: 0; background: #fff; }
            .sheet { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print toolbar">
        <button type="button" onclick="window.print()" class="btn btn-dark">Imprimer</button>
        <a href="{{ route('commercials.export') }}" class="btn btn-success">Exporter</a>
        <button type="button" onclick="window.close()" class="btn btn-light">Fermer</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div>
                <div class="logo">BELDI-MALAKI</div>
                <p class="meta">Liste des commerciaux</p>
            </div>
            <p class="meta">{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID commercial</th>
                    <th>Nom commercial</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Zone prospect</th>
                    <th>Commission (%)</th>
                    <th>CA vendu</th>
                    <th>Cmd. livrées</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commercials as $commercial)
                    <tr>
                        <td>{{ $commercial->formattedCommercialId() }}</td>
                        <td>{{ $commercial->name }}</td>
                        <td>{{ $commercial->phone ?? '—' }}</td>
                        <td>{{ $commercial->email }}</td>
                        <td>{{ $commercial->whatsapp ?? '—' }}</td>
                        <td>{{ $commercial->prospect_zone ?? '—' }}</td>
                        <td class="right">{{ $commercial->commission_rate !== null ? number_format($commercial->commission_rate, 1, ',', ' ') . ' %' : '—' }}</td>
                        <td class="right">{{ number_format($commercial->total_sales ?? 0, 0, ',', ' ') }} DH</td>
                        <td class="center">{{ $commercial->delivered_orders_count ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #64748b;">Aucun commercial</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
