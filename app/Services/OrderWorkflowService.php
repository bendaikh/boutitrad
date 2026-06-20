<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use App\Enums\RegulationStatus;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderWorkflowService
{
    public function __construct(
        private CathedisDispatchService $cathedis,
        private CathedisStatusSyncService $cathedisStatusSync,
        private CommissionService $commissionService,
        private AdminNotificationService $adminNotifications,
        private OrderStockService $orderStock,
    ) {}

    public function submitToAdmin(Order $order, User $user): Order
    {
        $this->assertCommercialOwner($order, $user);

        if ($order->status !== OrderStatus::Nouvelle) {
            throw new InvalidArgumentException('Seules les commandes nouvelles peuvent être envoyées à l\'admin.');
        }

        $order->loadMissing('items.product');

        if (! $order->isReadyForAdminSubmission()) {
            $missing = $order->missingItemsBeforeAdminSubmission();

            throw new InvalidArgumentException(
                'Complétez la commande avant envoi : '.implode(', ', $missing).'.'
            );
        }

        $updated = $this->transition($order, $user, OrderStatus::EnCours, [
            'submitted_to_admin_at' => now(),
        ], 'Commande envoyée à l\'admin pour validation');

        $this->adminNotifications->notifyOrderAwaitingValidation($updated);

        return $updated;
    }

    public function validateAndDispatchToPartner(Order $order, User $user, ?int $partnerId = null): Order
    {
        if (! $user->isSuperAdmin()) {
            throw new InvalidArgumentException('Seul l\'admin peut valider et envoyer au partenaire.');
        }

        $partner = $partnerId
            ? DeliveryPartner::query()->where('is_active', true)->findOrFail($partnerId)
            : DeliveryPartner::defaultPartner();

        if (! $partner) {
            throw new InvalidArgumentException('Aucun partenaire de livraison actif. Configurez un partenaire dans Livraison > Partenaires.');
        }

        $order->loadMissing('client.cityRecord');

        if ($order->status === OrderStatus::Confirmee && blank($order->partner_tracking_ref)) {
            return $this->retryPartnerDispatch($order, $user, $partner);
        }

        if (! in_array($order->status, [OrderStatus::EnCours, OrderStatus::Nouvelle], true)) {
            if ($order->hasBeenValidatedByAdmin()) {
                throw new InvalidArgumentException(
                    'Cette commande a déjà été validée (statut : '.$order->status->label().').'
                );
            }

            throw new InvalidArgumentException(
                'Cette commande ne peut plus être validée (statut : '.$order->status->label().').'
            );
        }

        $this->assertReadyForPartnerDispatch($order, $partner);

        return DB::transaction(function () use ($order, $user, $partner) {
            $order->update([
                'validated_at' => now(),
                'delivery_partner_id' => $partner->id,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $order->status->value,
                'notes' => 'Commande validée par l\'admin — transmission Cathedis',
                'user_id' => $user->id,
            ]);

            return $this->dispatchToPartner($order, $user, $partner);
        });
    }

    private function retryPartnerDispatch(Order $order, User $user, DeliveryPartner $partner): Order
    {
        $this->assertReadyForPartnerDispatch($order, $partner);

        return DB::transaction(function () use ($order, $user, $partner) {
            if ($order->delivery_partner_id !== $partner->id) {
                $order->update(['delivery_partner_id' => $partner->id]);
            }

            return $this->dispatchToPartner($order->fresh(['client', 'items', 'deliveryPartner']), $user, $partner);
        });
    }

    private function dispatchToPartner(Order $order, User $user, DeliveryPartner $partner): Order
    {
        $trackingRef = $this->cathedis->dispatch($order->fresh(['client', 'items', 'deliveryPartner']), $partner);

        $order->update([
            'partner_tracking_ref' => $trackingRef,
            'sent_to_partner_at' => now(),
            'validated_at' => $order->validated_at ?? now(),
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status->value,
            'notes' => "Transmise au partenaire {$partner->name}".($trackingRef ? " — Ref. {$trackingRef}" : ''),
            'user_id' => $user->id,
        ]);

        $order->refresh();

        if ($partner->isCathedis()) {
            $this->cathedisStatusSync->syncOrder($order);
            $order->refresh();
        }

        return $order;
    }

    public function completeDelivery(Order $order, User $user, array $data): Order
    {
        $this->assertPartnerAccess($order, $user);

        if (! $order->isDeliverableByPartner()) {
            throw new InvalidArgumentException('Cette commande n\'est pas en cours de livraison.');
        }

        $amountCollected = isset($data['amount_collected']) ? (float) $data['amount_collected'] : (float) $order->total;
        $outcome = $data['outcome'] ?? 'delivered';

        return DB::transaction(function () use ($order, $user, $amountCollected, $outcome, $data) {
            $previousStatus = $order->status;

            if ($outcome === 'returned') {
                $order->update([
                    'status' => OrderStatus::Retournee,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => OrderStatus::Retournee->value,
                    'notes' => $data['notes'] ?? 'Retour signalé par le partenaire',
                    'user_id' => $user->id,
                ]);

                $this->orderStock->restoreForOrder($order->fresh(['items.product']), $user);
            } else {
                $order->update([
                    'status' => OrderStatus::Livree,
                    'delivered_at' => now(),
                    'amount_paid' => round($order->paidAmount() + max(0, $amountCollected), 2),
                ]);

                if ($amountCollected > 0) {
                    OrderPayment::create([
                        'order_id' => $order->id,
                        'payment_date' => now()->toDateString(),
                        'payment_mode' => PaymentMode::Comptant,
                        'amount' => $amountCollected,
                        'regulation_status' => RegulationStatus::Paye,
                        'payment_number' => $order->partner_tracking_ref,
                        'created_by' => $user->id,
                    ]);
                }

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => OrderStatus::Livree->value,
                    'notes' => ($data['notes'] ?? 'Livraison effectuée').($amountCollected > 0 ? ' — COD: '.number_format($amountCollected, 2, ',', ' ').' DH' : ''),
                    'user_id' => $user->id,
                ]);

                $this->commissionService->syncAfterStatusChange($order, $previousStatus, OrderStatus::Livree);
                $this->commissionService->grantForDeliveredOrder($order);
            }

            return $order->fresh();
        });
    }

    public function rejectOrder(Order $order, User $user, ?string $notes = null): Order
    {
        if (! $user->isSuperAdmin()) {
            throw new InvalidArgumentException('Seul l\'admin peut rejeter une commande.');
        }

        if (! in_array($order->status, [OrderStatus::EnCours, OrderStatus::Nouvelle], true)) {
            throw new InvalidArgumentException('Cette commande ne peut plus être rejetée.');
        }

        return $this->transition($order, $user, OrderStatus::Annulee, [
            'cancelled_at' => now(),
        ], $notes ?? 'Commande rejetée par l\'admin', restoreStock: true);
    }

    public function allowedStatusesFor(User $user, Order $order): array
    {
        if ($user->isSuperAdmin()) {
            return OrderStatus::cases();
        }

        if ($user->isCommercial() && $order->commercial_id === $user->id && $order->status === OrderStatus::Nouvelle) {
            return [OrderStatus::Nouvelle, OrderStatus::EnCours];
        }

        return [];
    }

    private function transition(Order $order, User $user, OrderStatus $status, array $extra, string $note, bool $restoreStock = false): Order
    {
        $previousStatus = $order->status;

        $order->update(array_merge(['status' => $status], $extra));

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status->value,
            'notes' => $note,
            'user_id' => $user->id,
        ]);

        $order->refresh();

        if ($restoreStock) {
            $this->orderStock->restoreIfReleased($order, $previousStatus, $user);
        }

        $this->commissionService->syncAfterStatusChange($order, $previousStatus, $status);

        return $order;
    }

    private function assertCommercialOwner(Order $order, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if (! $user->isCommercial() || $order->commercial_id !== $user->id) {
            abort(403);
        }
    }

    private function assertPartnerAccess(Order $order, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->isLivreur()) {
            if ($order->livreur_id && $order->livreur_id !== $user->id) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    private function assertReadyForPartnerDispatch(Order $order, DeliveryPartner $partner): void
    {
        $client = $order->client;

        if (! $client) {
            throw new InvalidArgumentException('Client introuvable pour cette commande.');
        }

        if (! $partner->isCathedis()) {
            return;
        }

        if ($client->deliveryCityName() === '') {
            throw new InvalidArgumentException('Le client doit avoir une ville Cathedis avant envoi au partenaire.');
        }

        if (empty($client->phone)) {
            throw new InvalidArgumentException('Le client doit avoir un numéro de téléphone pour Cathedis.');
        }

        if (empty($client->address)) {
            throw new InvalidArgumentException('Le client doit avoir une adresse de livraison pour Cathedis.');
        }

        if (! filled($client->cityRecord?->cathedis_code)) {
            throw new InvalidArgumentException('La ville du client doit être synchronisée avec Cathedis (code ville manquant).');
        }
    }
}
