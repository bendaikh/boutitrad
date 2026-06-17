<?php

use App\Services\CathedisSessionService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$session = app(CathedisSessionService::class);
$apiUrl = rtrim(config('cathedis.api_url'), '/');
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

foreach ([
    'com.axelor.apps.base.db.Sector',
    'com.tracker.delivery.db.Sector',
    'com.tracker.settings.db.Sector',
] as $model) {
    $response = $client->asJson()->post("/ws/rest/{$model}/search", [
        'limit' => 3,
        'offset' => 0,
        'data' => ['city' => ['id' => 27]],
    ]);
    echo "=== {$model} status={$response->status()} ===\n";
    echo substr($response->body(), 0, 500)."\n\n";
}

$recipientPayload = [
    'name' => 'yahya bilal',
    'phone' => '0772494544',
    'address' => 'hay naiim',
    'city' => ['id' => 27, 'fullName' => 'Meknes', '$version' => 2],
    'sector' => ['id' => 2766, 'name' => 'Autre'],
];

echo "=== Recipient create ===\n";
$response = $client->asJson()->put('/ws/rest/com.tracker.delivery.db.Recipient', ['data' => $recipientPayload]);
echo $response->body()."\n";
