<?php

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Services\CathedisSessionService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$partner = DeliveryPartner::defaultPartner();
$apiUrl = rtrim($partner?->api_url ?: config('cathedis.api_url'), '/');
$session = app(CathedisSessionService::class);
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$order = Order::whereNotNull('partner_tracking_ref')->latest()->first();
$response = $client->asJson()->post('/ws/rest/com.tracker.delivery.db.Delivery/search', [
    'limit' => 1,
    'offset' => 0,
    'data' => ['code' => $order->partner_tracking_ref],
]);
$data = $response->json('data.0') ?? [];
echo "Full delivery keys with status-like values:\n";
foreach ($data as $key => $value) {
    if (is_scalar($value) && (stripos($key, 'status') !== false || stripos((string) $value, 'confirm') !== false)) {
        echo "  {$key} = {$value}\n";
    }
    if (is_array($value) && (stripos($key, 'status') !== false || stripos($key, 'stat') !== false)) {
        echo "  {$key} = ".json_encode($value, JSON_UNESCAPED_UNICODE)."\n";
    }
}

echo "\nScan 500 deliveries for confir* in deliveryStatus.code:\n";
$found = [];
for ($offset = 0; $offset < 500; $offset += 100) {
    $resp = $client->asJson()->post('/ws/rest/com.tracker.delivery.db.Delivery/search', [
        'limit' => 100,
        'offset' => $offset,
        'fields' => ['code', 'deliveryStatus', '$wkfStatus'],
    ]);
    foreach ($resp->json('data') ?? [] as $row) {
        $code = (string) data_get($row, 'deliveryStatus.code', '');
        $wkf = data_get($row, '$wkfStatus');
        if (stripos($code, 'confir') !== false || (is_string($wkf) && stripos($wkf, 'confir') !== false)) {
            $found[$code] = ($found[$code] ?? 0) + 1;
        }
    }
}
if ($found === []) {
    echo "  none found\n";
} else {
    foreach ($found as $code => $count) {
        echo "  {$code} ({$count})\n";
    }
}

// List all unique deliveryStatus from 500 records
$all = [];
for ($offset = 0; $offset < 500; $offset += 100) {
    $resp = $client->asJson()->post('/ws/rest/com.tracker.delivery.db.Delivery/search', [
        'limit' => 100,
        'offset' => $offset,
        'fields' => ['deliveryStatus'],
    ]);
    foreach ($resp->json('data') ?? [] as $row) {
        $code = (string) data_get($row, 'deliveryStatus.code', '');
        if ($code !== '') {
            $all[$code] = ($all[$code] ?? 0) + 1;
        }
    }
}
ksort($all);
echo "\nAll deliveryStatus codes in 500 records:\n";
foreach ($all as $code => $count) {
    echo "  {$code} ({$count})\n";
}
