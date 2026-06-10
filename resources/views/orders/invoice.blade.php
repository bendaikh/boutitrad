<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $order->reference }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: bold; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer;">Imprimer</button>
    </div>
    <div class="header">
        <div>
            <div class="logo">BoutiTrad</div>
            <p>Facture N° {{ $order->reference }}</p>
            <p>Date: {{ $order->created_at->format('d/m/Y') }}</p>
        </div>
        <div style="text-align: right;">
            <strong>{{ $order->client->name }}</strong><br>
            {{ $order->client->address }}<br>
            {{ $order->client->city }}<br>
            {{ $order->client->phone }}
        </div>
    </div>
    <table>
        <thead><tr><th>Produit</th><th>Qté</th><th>P.U.</th><th>Total</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                    <td>{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">Total TTC: {{ number_format($order->total, 2, ',', ' ') }} DH</div>
    <p style="margin-top: 40px; color: #666; font-size: 12px;">Merci pour votre confiance.</p>
</body>
</html>
