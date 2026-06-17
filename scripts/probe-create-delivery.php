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

$order = Order::where('reference', 'CMD-20260617-0001')->with('items')->first();
$itemsDesc = $order->items->map(fn ($i) => $i->product_name.' x'.$i->quantity)->join(', ');

$city = ['id' => 27, 'name' => 'Meknes', 'fullName' => 'Meknes', '$version' => 2];
$sector = ['id' => 2766, 'name' => 'Autre'];

$deliveryData = [
    'subject' => $itemsDesc,
    'phone' => $order->client->phone,
    'address' => $order->client->address,
    'amount' => '2400.00',
    'city' => $city,
    'sector' => $sector,
    'customer' => ['id' => 23055],
    'recipient' => ['id' => 7667957],
    'paymentType' => ['id' => 1],
    'deliveryType' => ['id' => 1],
    'deliveryStatus' => ['id' => 1, 'code' => 'En Attente Ramassage'],
    'allowOpening' => true,
    'nomOrder' => $order->reference,
    'comment' => $order->shipping_remark,
    'packageCount' => 1,
    'rangeWeight' => 'ONE_FIVE',
    'shippingMethod' => 'LAD',
    'typeDelivery' => 'NORMAL',
];

echo "--- Delivery create ---\n";
$response = $client->asJson()->put('/ws/rest/com.tracker.delivery.db.Delivery', ['data' => $deliveryData]);
echo $response->body()."\n";
