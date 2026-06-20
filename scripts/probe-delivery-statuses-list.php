<?php

use App\Models\DeliveryPartner;
use App\Services\CathedisSessionService;
use App\Support\CathedisConfig;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$partner = DeliveryPartner::defaultPartner();
$apiUrl = rtrim($partner?->api_url ?: config('cathedis.api_url'), '/');
$session = app(CathedisSessionService::class);
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$response = $client->asJson()->post('/ws/rest/com.tracker.delivery.db.Delivery/search', [
    'limit' => 200,
    'offset' => 0,
    'fields' => ['id', 'code', 'nomOrder', 'deliveryStatus', 'status', 'returnStatus'],
]);

$statuses = [];
foreach ($response->json('data') ?? [] as $row) {
    $code = data_get($row, 'deliveryStatus.code') ?? data_get($row, 'deliveryStatus.name');
    if ($code) {
        $statuses[$code] = ($statuses[$code] ?? 0) + 1;
    }
}

echo "Distinct deliveryStatus codes (sample 50):\n";
foreach ($statuses as $code => $count) {
    echo "  {$code} ({$count})\n";
}

// Try meta fields for deliveryStatus relation
$response = $client->get('/ws/meta/fields/com.tracker.delivery.db.Delivery');
foreach ($response->json('data') ?? [] as $field) {
    $name = $field['name'] ?? '';
    if (str_contains(strtolower($name), 'status') || str_contains(strtolower($name), 'statut')) {
        echo "Field: {$name} type=".($field['type'] ?? '?')."\n";
    }
}

// Search status dictionary models
foreach ([
    'com.tracker.delivery.db.Status',
    'com.tracker.delivery.db.DeliveryStep',
    'com.tracker.delivery.db.DeliveryState',
    'com.tracker.delivery.db.DeliveryStatusType',
    'com.tracker.delivery.db.DeliveryStatus',
] as $model) {
    $resp = $client->asJson()->post("/ws/rest/{$model}/search", ['limit' => 30, 'offset' => 0]);
    $status = $resp->json('status');
    if ((int) $status === 0) {
        echo "\n=== {$model} ===\n";
        foreach ($resp->json('data') ?? [] as $item) {
            $id = $item['id'] ?? '?';
            $code = $item['code'] ?? $item['name'] ?? $item['displayName'] ?? json_encode($item);
            echo "  [{$id}] {$code}\n";
        }
    }
}
