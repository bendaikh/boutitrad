<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — BELDI-MALAKI</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1400px; margin: 32px auto; color: #1e293b; background: #f8fafc; }
        .sheet { background: #fff; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { color: #64748b; font-size: 13px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 8px; }
        th { background: #334155; color: #f8fafc; font-weight: bold; text-align: left; }
        td.right, th.right { text-align: right; }
        td.center, th.center { text-align: center; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-dark { background: #00332B; color: #fff; }
        .btn-light { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .no-print { margin-bottom: 0; }
        .total { margin-top: 16px; text-align: right; font-size: 14px; font-weight: bold; }
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
            <a href="{{ route('reports.section.export.pdf', $section) }}" class="btn btn-light">Exporter PDF</a>
            <button type="button" onclick="window.close()" class="btn btn-light">Fermer</button>
        </div>
    @endunless

    <div class="sheet">
        <div class="header">
            <div>
                <div class="logo">BELDI-MALAKI</div>
                <p class="meta">Rapport — {{ $title }}</p>
            </div>
            <p class="meta">{{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    @foreach($headers as $index => $header)
                        <th class="{{ $alignments[$index] ?? 'left' }}">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $index => $cell)
                            <td class="{{ $alignments[$index] ?? 'left' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}" style="text-align:center;color:#64748b;">Aucune donnée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($totalLabel && $totalValue)
            <p class="total">{{ $totalLabel }} : {{ $totalValue }}</p>
        @endif
    </div>
</body>
</html>
