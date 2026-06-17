<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Models\Order::where('reference', 'CMD-20260617-0001')->first();
echo "status={$o->status->value} total={$o->total} balance={$o->balanceDue()} partner_ref={$o->partner_tracking_ref}\n";
