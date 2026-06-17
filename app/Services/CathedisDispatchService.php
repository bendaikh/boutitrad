<?php

namespace App\Services;

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Support\CathedisConfig;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CathedisDispatchService
{
    private const DELIVERY_ENDPOINT = '/ws/rest/com.tracker.delivery.db.Delivery';

    private const RECIPIENT_ENDPOINT = '/ws/rest/com.tracker.delivery.db.Recipient';

    public function __construct(private CathedisSessionService $session) {}

    public function dispatch(Order $order, DeliveryPartner $partner): string
    {
        if (! $partner->isCathedis()) {
            return $this->localTrackingRef($order, $partner);
        }

        if (! CathedisConfig::enabled() || ! CathedisConfig::isConfigured()) {
            throw new CathedisDispatchException(
                'API Cathedis non configurée. Enregistrez les identifiants dans Livraison > Partenaires.'
            );
        }

        $apiUrl = rtrim($partner->api_url ?: (string) config('cathedis.api_url'), '/');

        if ($this->session->credentialsConfigured()) {
            if (! $this->session->authenticate($apiUrl)) {
                throw new CathedisDispatchException(
                    'Connexion Cathedis refusée. Vérifiez le login et le mot de passe.'
                );
            }
        }

        $order->loadMissing(['client.cityRecord', 'items']);

        $trackingRef = $this->sendToCathedis($order, $apiUrl);

        if ($trackingRef === null) {
            throw new CathedisDispatchException(
                'La commande n\'a pas pu être transmise à Cathedis. Vérifiez ville, téléphone et adresse du client.'
            );
        }

        return $trackingRef;
    }

    private function sendToCathedis(Order $order, string $apiUrl): ?string
    {
        $client = $this->session->http($apiUrl);

        $recipientId = $this->ensureRecipient($client, $order);
        if ($recipientId === null) {
            return null;
        }

        $payload = $this->buildTrackerDeliveryPayload($order, $recipientId);

        $response = $client->asJson()->put(self::DELIVERY_ENDPOINT, ['data' => $payload]);
        $tracking = $this->extractTrackingRef($response);

        if ($tracking !== null) {
            return $tracking;
        }

        Log::warning('Cathedis dispatch rejected', [
            'order' => $order->reference,
            'endpoint' => self::DELIVERY_ENDPOINT,
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 800),
        ]);

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTrackerDeliveryPayload(Order $order, int $recipientId): array
    {
        $client = $order->client;
        $cityName = $client->deliveryCityName();
        $cityId = (int) $client->cityRecord?->cathedis_code;
        $itemsDesc = $order->items
            ->map(fn ($item) => $item->product_name.' x'.$item->quantity)
            ->join(', ');

        return [
            'subject' => filled($itemsDesc) ? $itemsDesc : 'Commande '.$order->reference,
            'phone' => $client->phone,
            'address' => $client->address,
            'amount' => number_format($order->balanceDue(), 2, '.', ''),
            'city' => [
                'id' => $cityId,
                'fullName' => $cityName,
            ],
            'sector' => [
                'id' => (int) config('cathedis.default_sector_id', 2766),
                'name' => config('cathedis.default_sector_name', 'Autre'),
            ],
            'customer' => ['id' => CathedisConfig::storeId()],
            'recipient' => ['id' => $recipientId],
            'paymentType' => ['id' => (int) config('cathedis.payment_type_id', 1)],
            'deliveryType' => ['id' => (int) config('cathedis.delivery_type_id', 1)],
            'deliveryStatus' => [
                'id' => (int) config('cathedis.delivery_status_id', 1),
                'code' => config('cathedis.delivery_status_code', 'En Attente Ramassage'),
            ],
            'allowOpening' => (bool) config('cathedis.allow_opening', false),
            'nomOrder' => $order->reference,
            'comment' => $order->shipping_remark ?: $order->notes,
            'packageCount' => 1,
            'rangeWeight' => config('cathedis.range_weight', 'ONE_FIVE'),
            'shippingMethod' => config('cathedis.shipping_method', 'LAD'),
            'typeDelivery' => config('cathedis.type_delivery', 'NORMAL'),
        ];
    }

    private function ensureRecipient(PendingRequest $client, Order $order): ?int
    {
        $clientModel = $order->client;
        $cityId = (int) $clientModel->cityRecord?->cathedis_code;
        $cityName = $clientModel->deliveryCityName();

        $existingId = $this->findRecipientIdByPhone($client, $clientModel->phone);
        if ($existingId !== null) {
            return $existingId;
        }

        $payload = [
            'name' => $clientModel->name,
            'phone' => $clientModel->phone,
            'address' => $clientModel->address,
            'city' => [
                'id' => $cityId,
                'fullName' => $cityName,
            ],
            'sector' => [
                'id' => (int) config('cathedis.default_sector_id', 2766),
                'name' => config('cathedis.default_sector_name', 'Autre'),
            ],
        ];

        $response = $client->asJson()->put(self::RECIPIENT_ENDPOINT, ['data' => $payload]);

        if ((int) ($response->json('status') ?? -1) !== 0) {
            Log::warning('Cathedis recipient create failed', [
                'order' => $order->reference,
                'phone' => $clientModel->phone,
                'body' => substr($response->body(), 0, 800),
            ]);

            return null;
        }

        $id = data_get($response->json(), 'data.0.id') ?? data_get($response->json(), 'data.id');

        return is_numeric($id) ? (int) $id : null;
    }

    private function findRecipientIdByPhone(PendingRequest $client, string $phone): ?int
    {
        $response = $client->asJson()->post(self::RECIPIENT_ENDPOINT.'/search', [
            'limit' => 1,
            'offset' => 0,
            'data' => ['phone' => $phone],
        ]);

        if ((int) ($response->json('status') ?? -1) !== 0) {
            return null;
        }

        $id = data_get($response->json(), 'data.0.id');

        return is_numeric($id) ? (int) $id : null;
    }

    private function extractTrackingRef(Response $response): ?string
    {
        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        if (! is_array($json)) {
            return null;
        }

        if (isset($json['status']) && (int) $json['status'] !== 0) {
            return null;
        }

        foreach (['code', 'tracking_number', 'trackingNumber', 'awb', 'reference'] as $key) {
            $value = data_get($json, $key) ?? data_get($json, "data.{$key}") ?? data_get($json, "data.0.{$key}");
            if (filled($value) && ! is_array($value)) {
                return (string) $value;
            }
        }

        $data = $json['data'] ?? null;
        if (is_array($data) && isset($data['id']) && ! is_array($data['id'])) {
            return (string) $data['id'];
        }

        return null;
    }

    private function localTrackingRef(Order $order, DeliveryPartner $partner): string
    {
        return strtoupper($partner->code).'-'.Str::upper(Str::substr($order->reference, -8)).'-'.now()->format('ymd');
    }
}
