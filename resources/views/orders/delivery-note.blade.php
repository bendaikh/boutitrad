<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de commande {{ $order->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 24px auto; padding: 0 16px; color: #1e293b; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid #00332B; }
        .brand { font-size: 22px; font-weight: bold; color: #00332B; }
        .meta { font-size: 13px; color: #64748b; line-height: 1.5; margin: 0; }
        .client-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; line-height: 1.5; }
        .client-box strong { display: block; font-size: 16px; margin-bottom: 4px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 0; }
        .items-table th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .items-table .col-image { width: 72px; }
        .items-table .col-qty { width: 56px; text-align: center; }
        .items-table .col-price { width: 96px; text-align: right; white-space: nowrap; }
        .items-table .col-total { width: 108px; text-align: right; white-space: nowrap; font-weight: 600; }
        .product-name { font-weight: 600; font-size: 14px; line-height: 1.3; margin: 0; }
        .product-sku { font-size: 11px; color: #64748b; font-family: monospace; margin: 2px 0 0; line-height: 1.3; }
        .product-images { display: flex; align-items: flex-start; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .image-box { width: 56px; height: 56px; border-radius: 8px; flex-shrink: 0; }
        .image-box.placeholder { border: 1px dashed #cbd5e1; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 10px; text-align: center; line-height: 1.15; padding: 4px; }
        .image-box.preview { border: 1px solid #e2e8f0; background: #fff; overflow: hidden; }
        .image-box.preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .image-box.upload { border: 1px dashed #cbd5e1; background: #f8fafc; width: auto; min-width: 112px; height: auto; min-height: 56px; padding: 6px; display: flex; flex-direction: column; align-items: stretch; justify-content: center; gap: 4px; }
        .upload-form { display: flex; flex-direction: column; gap: 4px; width: 100%; }
        .upload-form input[type="file"] { font-size: 9px; width: 100%; }
        .btn-add-image { display: inline-flex; align-items: center; justify-content: center; gap: 3px; padding: 4px 8px; border: 1px solid #99f6e4; border-radius: 5px; background: #ecfdf5; color: #0f766e; font-size: 10px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .btn-add-image:hover { background: #ccfbf1; }
        .totals { margin-top: 12px; text-align: right; font-size: 14px; line-height: 1.65; }
        .totals .grand { font-size: 18px; font-weight: bold; color: #00332B; margin-top: 4px; }
        .totals .cod { margin-top: 2px; color: #b45309; }
        .nb-box { margin-top: 20px; padding: 12px 14px; border: 1px dashed #94a3b8; border-radius: 8px; background: #fffbeb; min-height: 56px; }
        .nb-label { font-size: 12px; font-weight: bold; color: #92400e; margin-bottom: 4px; }
        .nb-text { font-size: 14px; white-space: pre-wrap; color: #1e293b; margin: 0; }
        .footer { margin-top: 24px; font-size: 11px; color: #94a3b8; text-align: center; }
        .flash-success { margin-bottom: 12px; padding: 8px 12px; border-radius: 8px; background: #ecfdf5; border: 1px solid #99f6e4; color: #0f766e; font-size: 13px; }
        .flash-error { margin-bottom: 12px; padding: 8px 12px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 13px; }
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .image-box.upload { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #00332B; color: white; border: none; border-radius: 6px; cursor: pointer;">Imprimer</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; cursor: pointer;">Fermer</button>
    </div>

    @if(session('success'))
        <div class="flash-success no-print">{{ session('success') }}</div>
    @endif
    @if($errors->has('product_image'))
        <div class="flash-error no-print">{{ $errors->first('product_image') }}</div>
    @endif

    <div class="header">
        <div>
            <div class="brand">BELDI-MALAKI</div>
            <p class="meta" style="margin-top: 6px; font-weight: bold; color: #1e293b;">Bon de commande validé</p>
            <p class="meta">Réf. {{ $order->reference }}</p>
            <p class="meta">Date : {{ ($order->validated_at ?? $order->created_at)->format('d/m/Y H:i') }}</p>
            @if($order->partner_tracking_ref)
                <p class="meta">Ref. livraison : <strong>{{ $order->partner_tracking_ref }}</strong></p>
            @endif
        </div>
        <div class="meta" style="text-align: right;">
            @if($order->deliveryPartner)
                <div>Partenaire : <strong>{{ $order->deliveryPartner->name }}</strong></div>
            @endif
            @if($order->commercial)
                <div>Commercial : {{ $order->commercial->name }}</div>
            @endif
            <div>Statut : {{ $order->status->label() }}</div>
        </div>
    </div>

    <div class="client-box">
        <strong>{{ $order->client->name }}</strong>
        @if($order->client->address)<div>{{ $order->client->address }}</div>@endif
        @if($order->client->deliveryCityName())<div>{{ $order->client->deliveryCityName() }}</div>@endif
        @if($order->client->phone)<div>Tél. {{ $order->client->phone }}</div>@endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-image">Image</th>
                <th>Produit</th>
                <th class="col-qty">Qté</th>
                <th class="col-price">P.U.</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php $hasImage = (bool) $item->product?->imageUrl(); @endphp
                <tr>
                    <td class="col-image"></td>
                    <td>
                        <p class="product-name">{{ $item->product_name }}</p>
                        @if($item->product?->sku)
                            <p class="product-sku">{{ $item->product->sku }}</p>
                        @endif
                        <div class="product-images">
                            @if(! $hasImage)
                                <div @class([
                                    'image-box placeholder',
                                    'no-print' => $order->canUploadProductImage() && $item->product_id,
                                ])>Sans<br>image</div>
                            @endif
                            @if($order->canUploadProductImage() && $item->product_id)
                                <div class="image-box upload no-print">
                                    <form method="POST" action="{{ route('orders.items.product-image', [$order, $item]) }}" enctype="multipart/form-data" class="upload-form">
                                        @csrf
                                        <input type="hidden" name="return_to" value="print">
                                        <input type="file" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp,.jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn-add-image">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $hasImage ? 'Changer image' : 'Ajouter image' }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                            @if($hasImage)
                                <div class="image-box preview">
                                    <img src="{{ $item->product->imageUrl() }}" alt="{{ $item->product_name }}">
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="col-qty">{{ $item->quantity }}</td>
                    <td class="col-price">{{ number_format($item->unit_price, 2, ',', ' ') }} DH</td>
                    <td class="col-total">{{ number_format($item->total, 2, ',', ' ') }} DH</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div>Sous-total : {{ number_format($order->subtotal, 2, ',', ' ') }} DH</div>
        @if($order->delivery_cost > 0)
            <div>Livraison : {{ number_format($order->delivery_cost, 2, ',', ' ') }} DH</div>
        @endif
        @if($order->discount > 0)
            <div>Remise : -{{ number_format($order->discount, 2, ',', ' ') }} DH</div>
        @endif
        <div class="grand">Total : {{ number_format($order->total, 2, ',', ' ') }} DH</div>
        @if($order->balanceDue() > 0)
            <div class="cod">COD à encaisser : {{ number_format($order->balanceDue(), 2, ',', ' ') }} DH</div>
        @endif
    </div>

    <div class="nb-box">
        <div class="nb-label">NB — Remarque</div>
        <p class="nb-text">{{ $order->shipping_remark ?: '—' }}</p>
    </div>

    @if($order->notes)
        <div class="nb-box" style="background: #f8fafc; border-color: #e2e8f0; margin-top: 10px;">
            <div class="nb-label" style="color: #475569;">Notes commande</div>
            <p class="nb-text">{{ $order->notes }}</p>
        </div>
    @endif

    <p class="footer">Document généré le {{ now()->format('d/m/Y H:i') }} — Beldi-Malaki</p>
</body>
</html>
