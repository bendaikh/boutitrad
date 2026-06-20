<?php

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Services\CathedisSessionService;
use App\Support\CathedisConfig;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'Cathedis enabled: '.(CathedisConfig::enabled() ? 'yes' : 'no')."\n";
echo 'Cathedis configured: '.(CathedisConfig::isConfigured() ? 'yes' : 'no')."\n";

if (! CathedisConfig::enabled() || ! CathedisConfig::isConfigured()) {
    exit(0);
}

$partner = DeliveryPartner::defaultPartner();
$apiUrl = rtrim($partner?->api_url ?: config('cathedis.api_url'), '/');
$session = app(CathedisSessionService::class);
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$order = Order::query()
    ->whereNotNull('partner_tracking_ref')
    ->latest('sent_to_partner_at')
    ->first();

if (! $order) {
    $order = Order::query()->latest()->first();
}

if (! $order) {
    echo "No orders found\n";
    exit(0);
}

echo "Local order: {$order->reference} status={$order->status->value} tracking={$order->partner_tracking_ref}\n\n";

$deliveryEndpoint = '/ws/rest/com.tracker.delivery.db.Delivery';
$statusEndpoint = '/ws/rest/com.tracker.delivery.db.DeliveryStatus/search';

foreach ([
    ['by code', ['code' => $order->partner_tracking_ref]],
    ['by nomOrder', ['nomOrder' => $order->reference]],
] as [$label, $criteria]) {
    if (array_values($criteria)[0] === null || array_values($criteria)[0] === '') {
        continue;
    }

    echo "=== Search delivery {$label} ===\n";
    $response = $client->asJson()->post($deliveryEndpoint.'/search', [
        'limit' => 1,
        'offset' => 0,
        'data' => $criteria,
    ]);
    echo 'HTTP '.$response->status()."\n";
    echo substr(json_encode($response->json(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 0, 3000)."\n\n";
}

echo "=== DeliveryStatus sample ===\n";
$response = $client->asJson()->post($statusEndpoint, [
    'limit' => 20,
    'offset' => 0,
    'fields' => ['id', 'code', 'name', 'displayName'],
]);
echo substr(json_encode($response->json(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 0, 4000)."\n";
