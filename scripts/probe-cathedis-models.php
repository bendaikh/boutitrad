<?php

use App\Models\Order;
use App\Services\CathedisSessionService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$session = app(CathedisSessionService::class);
$apiUrl = rtrim(config('cathedis.api_url'), '/');
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$models = [
    'com.cathedis.db.DeliveryRequest',
    'com.cathedis.db.Colis',
    'com.cathedis.db.Shipment',
    'com.cathedis.db.DeliveryOrder',
    'com.cathedis.db.DeliveryBatch',
    'com.cathedis.db.DeliveryNote',
    'com.cathedis.db.PickupRequest',
    'com.cathedis.db.Waybill',
    'com.cathedis.db.Awb',
    'com.cathedis.db.DeliveryCity',
    'com.cathedis.db.Delivery',
    'com.cathedis.db.Order',
    'com.cathedis.db.CustomerOrder',
    'com.cathedis.db.ShippingOrder',
    'com.cathedis.db.Livraison',
    'com.cathedis.db.Colisage',
    'com.axelor.apps.delivery.db.Delivery',
    'com.axelor.apps.delivery.db.DeliveryRequest',
    'com.axelor.apps.stock.db.StockMove',
    'com.axelor.apps.sale.db.SaleOrder',
];

foreach ($models as $model) {
    $endpoint = "/ws/rest/{$model}/search";
    $response = $client->asJson()->post($endpoint, ['limit' => 1, 'offset' => 0, 'fields' => ['id', 'name', 'reference']]);
    $json = $response->json();
    $status = $json['status'] ?? '?';
    $count = is_array($json['data'] ?? null) && array_is_list($json['data']) ? count($json['data']) : 0;
    if ($status === 0) {
        echo "OK  {$model} count={$count}\n";
        if ($count > 0) {
            echo json_encode($json['data'][0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
        }
    } elseif ($status !== -1) {
        echo "??  {$model} status={$status} ".substr(json_encode($json), 0, 120)."\n";
    }
}

$actions = [
    '/ws/action/com.cathedis.web.Delivery:create',
    '/ws/action/com.cathedis.web.Delivery:save',
    '/ws/action/com.cathedis.web.Colis:create',
    '/ws/action/com.cathedis.web.DeliveryRequest:create',
    '/ws/action/com.cathedis.web.DeliveryRequest:save',
    '/ws/action/com.cathedis.web.Shipment:create',
    '/ws/action/com.cathedis.web.Order:create',
    '/ws/action/com.cathedis.web.DeliveryOrder:create',
];

$order = Order::where('reference', 'CMD-20260617-0001')->with('client.cityRecord')->first();
$data = [
    'recipientName' => $order->client->name,
    'recipientPhone' => $order->client->phone,
    'recipientAddress' => $order->client->address,
    'recipientCity' => $order->client->deliveryCityName(),
    'city' => ['id' => (int) $order->client->cityRecord?->cathedis_code],
    'codAmount' => (float) $order->total,
    'reference' => $order->reference,
];

echo "\n--- ACTIONS ---\n";
foreach ($actions as $action) {
    $response = $client->asJson()->post($action, ['data' => $data]);
    $body = substr(preg_replace('/\s+/', ' ', $response->body()), 0, 250);
    if (! str_contains($body, '404') && ! str_contains($body, 'Erreur interne')) {
        echo "[{$response->status()}] {$action}\n{$body}\n\n";
    }
}
