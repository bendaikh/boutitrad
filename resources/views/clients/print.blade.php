<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche client {{ $client->formattedId() }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #00332B; padding-bottom: 16px; }
        .logo { font-size: 22px; font-weight: bold; color: #00332B; }
        .photo { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { width: 35%; background: #f1f5f9; color: #1e293b; font-weight: 600; }
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
            <p style="margin: 8px 0 0; color: #64748b;">Fiche client</p>
            <p style="margin: 4px 0 0; font-weight: bold;">{{ $client->formattedId() }}</p>
        </div>
        @if($client->photoUrl())
            <img src="{{ $client->photoUrl() }}" alt="{{ $client->name }}" class="photo">
        @endif
    </div>

    <table>
        <tr><th>Nom</th><td>{{ $client->name }}</td></tr>
        <tr><th>Statut</th><td>{{ $client->is_active ? 'Actif' : 'Inactif' }}</td></tr>
        <tr><th>Téléphone</th><td>{{ $client->phone ?? '—' }}</td></tr>
        <tr><th>E-mail</th><td>{{ $client->email ?? '—' }}</td></tr>
        <tr><th>Ville</th><td>{{ $client->city ?? '—' }}</td></tr>
        <tr><th>Adresse</th><td>{{ $client->address ?? '—' }}</td></tr>
        <tr><th>Prospection</th><td>{{ $client->prospection?->label() ?? '—' }}</td></tr>
        <tr><th>Mode paiement</th><td>{{ $client->payment_mode?->label() ?? '—' }}</td></tr>
        <tr><th>Commercial</th><td>{{ $client->commercial?->name ?? '—' }}</td></tr>
        <tr><th>Facebook</th><td>{{ $client->facebook_page ?? '—' }}</td></tr>
        <tr><th>Instagram</th><td>{{ $client->instagram_page ?? '—' }}</td></tr>
        <tr><th>Solde</th><td>{{ number_format($client->balance, 2, ',', ' ') }} DH</td></tr>
        @if($client->notes)
            <tr><th>Notes</th><td>{{ $client->notes }}</td></tr>
        @endif
    </table>
</body>
</html>
