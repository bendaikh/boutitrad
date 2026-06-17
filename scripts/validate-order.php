<?php

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = Order::where('reference', 'CMD-20260617-0001')->first();
if (! $order) {
    echo "Commande introuvable.\n";
    exit(1);
}

$admin = User::query()->where('role', UserRole::SuperAdmin)->first()
    ?? User::query()->first();

if (! $admin) {
    echo "Aucun utilisateur admin trouvé.\n";
    exit(1);
}

try {
    $updated = app(OrderWorkflowService::class)->validateAndDispatchToPartner($order, $admin);
    echo "OK status={$updated->status->value} tracking={$updated->partner_tracking_ref}\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n";
    exit(1);
}
