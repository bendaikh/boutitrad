<?php

namespace App\Services;

use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CathedisDispatchService
{
    public function __construct(private CathedisSessionService $session) {}

    public function dispatch(Order $order, DeliveryPartner $partner): ?string
    {
        if (! $partner->isCathedis()) {
            return $this->localTrackingRef($order, $partner);
        }

        $apiUrl = rtrim($partner->api_url ?: (string) config('cathedis.api_url'), '/');

        if (! config('cathedis.enabled') || ! $this->session->isConfigured()) {
            return $this->localTrackingRef($order, $partner);
        }

        if ($this->session->credentialsConfigured()) {
            $this->session->authenticate($apiUrl);
        }

        try {
            $client = $order->client;
            $cityName = $client->deliveryCityName();
            $cityCode = $client->cityRecord?->cathedis_code;

            $payload = [
                'reference' => $order->reference,
                'recipient_name' => $client->name,
                'recipient_phone' => $client->phone,
                'recipient_address' => $client->address,
                'recipient_city' => $cityName,
                'city' => $cityName,
                'city_code' => $cityCode,
                'city_id' => $cityCode,
                'pickup_city' => config('cathedis.pickup_city'),
                'cod_amount' => (float) $order->balanceDue(),
                'order_amount' => (float) $order->total,
                'notes' => $order->notes,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->unit_price,
                ])->values()->all(),
            ];

            $response = $this->session->http($apiUrl)->post('/parcels', $payload);

            if ($response->successful()) {
                return $response->json('tracking_number')
                    ?? $response->json('awb')
                    ?? $response->json('reference')
                    ?? $this->localTrackingRef($order, $partner);
            }

            Log::warning('Cathedis dispatch failed', [
                'order' => $order->reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Cathedis dispatch error', [
                'order' => $order->reference,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->localTrackingRef($order, $partner);
    }

    private function localTrackingRef(Order $order, DeliveryPartner $partner): string
    {
        return strtoupper($partner->code).'-'.Str::upper(Str::substr($order->reference, -8)).'-'.now()->format('ymd');
    }
}
