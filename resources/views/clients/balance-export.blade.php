<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Balance {{ $client->formattedId() }} — {{ $client->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1100px; margin: 32px auto; color: #1e293b; background: #f8fafc; }
        .sheet { background: #fff; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 9px 10px; text-align: left; }
        th { background: #334155; color: #f8fafc; font-weight: bold; }
        td.num { text-align: right; }
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
    @unless($forPdf ?? false)
        <div class="no-print toolbar">
            <button type="button" onclick="window.print()" class="btn btn-dark">Imprimer</button>
            <a href="{{ route('clients.balance.export.pdf', $client) }}" class="btn btn-light">Exporter PDF</a>
            <a href="{{ route('clients.balance.export.excel', $client) }}" class="btn btn-success">Exporter Excel</a>
            <button type="button" onclick="window.close()" class="btn btn-light">Fermer</button>
        </div>
    @endunless

    <div class="sheet">
        <div class="header">
            <div>
                <div class="logo">BELDI-MALAKI</div>
                <p class="meta">Balance client</p>
                <p class="meta"><strong>{{ $client->formattedId() }}</strong> — {{ $client->name }}</p>
            </div>
            <p class="meta">{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom client</th>
                    <th>Date commande</th>
                    <th>Désignation</th>
                    <th>Montant</th>
                    <th>Type règl.</th>
                    <th>Solde</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['nom'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['designation'] }}</td>
                        <td class="num">{{ number_format($row['montant'], 2, ',', ' ') }} DH</td>
                        <td>{{ $row['type_regl'] }}</td>
                        <td class="num">{{ number_format($row['solde'], 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Aucune ligne</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
