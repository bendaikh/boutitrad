<?php

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Services\CathedisDispatchService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = Order::where('reference', 'CMD-20260617-0001')->with(['client.cityRecord', 'items'])->first();
$partner = DeliveryPartner::where('code', 'cathedis')->first();

try {
    $ref = app(CathedisDispatchService::class)->dispatch($order, $partner);
    echo "OK tracking={$ref}\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n";
}
