<?php

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\User;
use App\Services\CathedisDispatchService;
use App\Services\CathedisSessionService;
use App\Support\CathedisConfig;
use Illuminate\Support\Facades\Log;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = Order::where('reference', 'CMD-20260617-0001')->with(['client.cityRecord', 'items'])->first();
if (! $order) {
    echo "Order not found\n";
    exit(1);
}

echo "Order: {$order->reference} status={$order->status->value}\n";
echo "Client: {$order->client->name} | {$order->client->phone} | {$order->client->address} | {$order->client->deliveryCityName()}\n";
echo "Cathedis enabled: ".(CathedisConfig::enabled() ? 'yes' : 'no')."\n";
echo "Cathedis configured: ".(CathedisConfig::isConfigured() ? 'yes' : 'no')."\n";

$partner = DeliveryPartner::defaultPartner();
echo "Partner: ".($partner?->name ?? 'none')."\n\n";

$session = app(CathedisSessionService::class);
$apiUrl = rtrim($partner?->api_url ?: config('cathedis.api_url'), '/');
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$payload = [
    'reference' => $order->reference,
    'recipientName' => $order->client->name,
    'recipientPhone' => $order->client->phone,
    'recipientAddress' => $order->client->address,
    'recipientCity' => $order->client->deliveryCityName(),
    'cityCode' => $order->client->cityRecord?->cathedis_code,
    'codAmount' => (float) $order->balanceDue(),
    'amount' => (float) $order->total,
    'pickupCity' => config('cathedis.pickup_city'),
];

$endpoints = config('cathedis.delivery_endpoints', []);

foreach ($endpoints as $endpoint) {
    echo "=== {$endpoint} ===\n";
    if (str_starts_with($endpoint, '/ws/rest/')) {
        foreach (['PUT', 'POST'] as $method) {
            $response = $method === 'PUT'
                ? $client->asJson()->put($endpoint, ['data' => $payload])
                : $client->asJson()->post($endpoint, ['data' => $payload]);
            echo "{$method} HTTP {$response->status()}\n";
            echo substr($response->body(), 0, 400)."\n\n";
        }
    } else {
        $response = $client->asJson()->post($endpoint, $payload);
        echo "POST HTTP {$response->status()}\n";
        echo substr($response->body(), 0, 400)."\n\n";
    }
}

// Probe additional Axelor models
$models = [
    'com.cathedis.db.DeliveryRequest',
    'com.cathedis.db.Colis',
    'com.cathedis.db.Shipment',
    'com.cathedis.db.DeliveryOrder',
    'com.cathedis.db.DeliveryBatch',
];

foreach ($models as $model) {
    $endpoint = "/ws/rest/{$model}/search";
    $response = $client->asJson()->post($endpoint, ['limit' => 1, 'offset' => 0]);
    $json = $response->json();
    $status = $json['status'] ?? '?';
    if ($status === 0) {
        echo "FOUND MODEL: {$model}\n";
        echo substr(json_encode($json), 0, 500)."\n\n";
    }
}

try {
    $dispatch = app(CathedisDispatchService::class);
    $ref = $dispatch->dispatch($order, $partner);
    echo "Dispatch OK: {$ref}\n";
} catch (Throwable $e) {
    echo "Dispatch FAILED: {$e->getMessage()}\n";
}
