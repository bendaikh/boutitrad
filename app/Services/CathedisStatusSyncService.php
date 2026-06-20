<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Support\CathedisConfig;
use App\Support\CathedisStatusMapper;
use Illuminate\Support\Facades\Log;

class CathedisStatusSyncService
{
    private const DELIVERY_ENDPOINT = '/ws/rest/com.tracker.delivery.db.Delivery';

    public function __construct(
        private CathedisSessionService $session,
        private CommissionService $commissionService,
    ) {}

    /**
     * @return array{updated: int, checked: int, skipped: int, errors: int}
     */
    public function syncPendingOrders(?int $limit = null): array
    {
        $summary = [
            'updated' => 0,
            'checked' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        if (! CathedisConfig::enabled() || ! CathedisConfig::isConfigured()) {
            return $summary;
        }

        $query = Order::query()
            ->whereNotNull('partner_tracking_ref')
            ->whereHas('deliveryPartner', fn ($q) => $q->where('code', 'cathedis'))
            ->orderByDesc('sent_to_partner_at')
            ->orderByDesc('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->each(function (Order $order) use (&$summary) {
            $summary['checked']++;

            try {
                $result = $this->syncOrder($order);

                if ($result === 'updated') {
                    $summary['updated']++;
                } elseif ($result === 'skipped') {
                    $summary['skipped']++;
                }
            } catch (\Throwable $e) {
                $summary['errors']++;
                Log::warning('Cathedis status sync failed', [
                    'order' => $order->reference,
                    'tracking' => $order->partner_tracking_ref,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        return $summary;
    }

    public function syncOrder(Order $order): string
    {
        $order->loadMissing('deliveryPartner');

        if (! $this->canRefreshStatus($order)) {
            return 'skipped';
        }

        $remote = $this->fetchRemoteStatus($order);

        if ($remote === null) {
            return 'skipped';
        }

        $cathedisStatus = (string) ($remote['status_code'] ?? '');

        $order->update([
            'cathedis_status_code' => $cathedisStatus !== '' ? $cathedisStatus : null,
            'cathedis_status_synced_at' => now(),
        ]);

        if (! $this->canApplyLocalStatus($order->fresh())) {
            return 'skipped';
        }

        $targetStatus = CathedisStatusMapper::map($cathedisStatus);

        if ($targetStatus === null) {
            return 'skipped';
        }

        if (! CathedisStatusMapper::shouldApply($order->fresh()->status, $targetStatus)) {
            return 'skipped';
        }

        return $this->applyStatus($order->fresh(), $targetStatus, $cathedisStatus) ? 'updated' : 'skipped';
    }

    /**
     * @return array{status_code: string, tracking_code: ?string, raw: array<string, mixed>}|null
     */
    public function fetchRemoteStatus(Order $order): ?array
    {
        $partner = $order->deliveryPartner ?? DeliveryPartner::defaultPartner();

        if (! $partner?->isCathedis()) {
            return null;
        }

        $apiUrl = rtrim($partner->api_url ?: (string) config('cathedis.api_url'), '/');

        if ($this->session->credentialsConfigured()) {
            $this->session->authenticate($apiUrl);
        }

        $client = $this->session->http($apiUrl);
        $delivery = $this->searchDelivery($client, ['code' => $order->partner_tracking_ref])
            ?? $this->searchDelivery($client, ['nomOrder' => $order->reference]);

        if ($delivery === null) {
            return null;
        }

        $statusCode = trim((string) (
            data_get($delivery, 'deliveryStatus.code')
            ?? data_get($delivery, 'deliveryStatus.name')
            ?? data_get($delivery, 'deliveryStatus')
            ?? ''
        ));

        return [
            'status_code' => $statusCode,
            'tracking_code' => filled(data_get($delivery, 'code')) ? (string) data_get($delivery, 'code') : null,
            'raw' => $delivery,
        ];
    }

    private function canRefreshStatus(Order $order): bool
    {
        return filled($order->partner_tracking_ref)
            && $order->deliveryPartner?->isCathedis();
    }

    private function canApplyLocalStatus(Order $order): bool
    {
        if (! $this->canRefreshStatus($order)) {
            return false;
        }

        return ! in_array($order->status, [OrderStatus::Annulee, OrderStatus::Livree, OrderStatus::Retournee], true);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>|null
     */
    private function searchDelivery($client, array $criteria): ?array
    {
        if (blank(array_values($criteria)[0] ?? null)) {
            return null;
        }

        $response = $client->asJson()->post(self::DELIVERY_ENDPOINT.'/search', [
            'limit' => 1,
            'offset' => 0,
            'fields' => ['id', 'code', 'nomOrder', 'deliveryStatus', 'status', 'returnStatus'],
            'data' => $criteria,
        ]);

        if ((int) ($response->json('status') ?? -1) !== 0) {
            return null;
        }

        $row = $response->json('data.0');

        return is_array($row) ? $row : null;
    }

    private function applyStatus(Order $order, OrderStatus $targetStatus, string $cathedisStatus): bool
    {
        $previousStatus = $order->status;
        $updates = ['status' => $targetStatus];

        if ($targetStatus === OrderStatus::Confirmee && $order->validated_at === null) {
            $updates['validated_at'] = now();
        }

        if ($targetStatus === OrderStatus::Livree && $order->delivered_at === null) {
            $updates['delivered_at'] = now();
        }

        if ($targetStatus === OrderStatus::Annulee && $order->cancelled_at === null) {
            $updates['cancelled_at'] = now();
        }

        $order->update($updates);
        $order->refresh();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $targetStatus->value,
            'notes' => 'Statut Cathedis : '.$cathedisStatus,
            'user_id' => null,
        ]);

        $this->commissionService->syncAfterStatusChange($order, $previousStatus, $targetStatus);

        if ($targetStatus === OrderStatus::Livree) {
            $this->commissionService->grantForDeliveredOrder($order);
        }

        return true;
    }
}
