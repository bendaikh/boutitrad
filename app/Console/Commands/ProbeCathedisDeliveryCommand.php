<?php

namespace App\Console\Commands;

use App\Services\CathedisSessionService;
use Illuminate\Console\Command;

class ProbeCathedisDeliveryCommand extends Command
{
    protected $signature = 'cathedis:probe-delivery';

    protected $description = 'Explore les endpoints Cathedis pour créer une livraison';

    public function handle(CathedisSessionService $session): int
    {
        $baseUrl = rtrim((string) config('cathedis.api_url'), '/');

        if (! $session->isConfigured()) {
            $this->error('Cathedis non configuré.');

            return self::FAILURE;
        }

        $session->authenticate($baseUrl);
        $client = $session->http($baseUrl);

        foreach (['/ws/rest', '/ws/action', '/ws/public'] as $root) {
            $response = $client->get($root);
            $this->line("[{$response->status()}] GET {$root} | ".substr(preg_replace('/\s+/', ' ', $response->body()), 0, 300));
        }

        $models = [
            'com.cathedis.db.Colis',
            'com.cathedis.db.Colisage',
            'com.cathedis.db.Livraison',
            'com.cathedis.db.Order',
            'com.cathedis.db.DeliveryRequest',
            'com.cathedis.db.DeliveryRequestLine',
            'com.cathedis.db.DeliveryBatch',
            'com.cathedis.db.DeliveryNote',
            'com.cathedis.db.Pickup',
            'com.cathedis.db.PickupRequest',
            'com.cathedis.db.ShippingOrder',
            'com.cathedis.db.Shipping',
            'com.cathedis.db.Waybill',
            'com.cathedis.db.Awb',
            'com.cathedis.db.DeliveryCity',
        ];

        foreach ($models as $model) {
            $endpoint = "/ws/rest/{$model}/search";
            $response = $client->asJson()->post($endpoint, ['limit' => 1, 'offset' => 0]);
            $json = $response->json();
            $status = $json['status'] ?? '?';
            $count = is_array($json['data'] ?? null) && array_is_list($json['data']) ? count($json['data']) : 0;
            if ($status !== -1 || $count > 0) {
                $this->line("[{$response->status()}] {$model} status={$status} count={$count}");
            }
        }

        $actions = [
            '/ws/action/com.cathedis.web.Delivery:create',
            '/ws/action/com.cathedis.web.Delivery:save',
            '/ws/action/com.cathedis.web.Colis:create',
            '/ws/action/com.cathedis.web.Order:create',
            '/ws/action/com.cathedis.web.Shipment:create',
        ];

        foreach ($actions as $action) {
            $response = $client->asJson()->post($action, ['data' => []]);
            $this->line("[{$response->status()}] POST {$action} | ".substr(preg_replace('/\s+/', ' ', $response->body()), 0, 200));
        }

        return self::SUCCESS;
    }
}
