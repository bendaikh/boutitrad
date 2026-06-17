<?php

use App\Services\CathedisSessionService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$session = app(CathedisSessionService::class);
$apiUrl = rtrim(config('cathedis.api_url'), '/');
$session->authenticate($apiUrl);
$client = $session->http($apiUrl);

$response = $client->get('/ws/rest/com.axelor.apps.base.db.City/27/fetch');
echo "City fetch:\n".$response->body()."\n\n";

$response = $client->asJson()->post('/ws/rest/com.tracker.delivery.db.Recipient/search', [
    'limit' => 1,
    'offset' => 0,
    'data' => ['phone' => '0772494544'],
]);
echo "Recipient search:\n".$response->body()."\n";
