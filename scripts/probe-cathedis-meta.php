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

$response = $client->get('/ws/meta/models');
$models = $response->json('data') ?? [];
echo "Models (".count($models)."):\n";
foreach ($models as $model) {
    echo "  {$model}\n";
}

echo "\n--- Search each model ---\n";
foreach ($models as $model) {
    $endpoint = "/ws/rest/{$model}/search";
    $response = $client->asJson()->post($endpoint, ['limit' => 1, 'offset' => 0]);
    $json = $response->json();
    $status = $json['status'] ?? '?';
    $count = is_array($json['data'] ?? null) && array_is_list($json['data']) ? count($json['data']) : 0;
    if ($status === 0) {
        echo "OK  {$model} (sample count={$count})\n";
        if ($count > 0) {
            echo json_encode($json['data'][0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n\n";
        }
    }
}

$order = Order::where('reference', 'CMD-20260617-0001')->with('client.cityRecord', 'items')->first();

// Get fields for key models
foreach (['com.tracker.delivery.db.Delivery', 'com.tracker.delivery.db.DeliveryRequest', 'com.tracker.crm.db.Pickup'] as $model) {
    if (! in_array($model, $models, true)) {
        continue;
    }
    $response = $client->get('/ws/meta/fields/'.$model);
    $fields = $response->json('data') ?? [];
    echo "\n=== Fields {$model} ===\n";
    foreach ($fields as $field) {
        $name = $field['name'] ?? '?';
        $type = $field['type'] ?? '';
        if (! str_starts_with($name, '$') && ! str_starts_with($name, '_')) {
            echo "  {$name} ({$type})\n";
        }
    }
}
