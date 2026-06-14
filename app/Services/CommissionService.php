<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

class CommissionService
{
    public function grantForDeliveredOrder(Order $order): ?Commission
    {
        if ($order->status !== OrderStatus::Livree || ! $order->commercial_id) {
            return null;
        }

        $commercial = User::query()
            ->where('id', $order->commercial_id)
            ->where('role', UserRole::Commercial)
            ->first();

        if (! $commercial) {
            return null;
        }

        $rate = $this->rateForCommercial($commercial);
        $amount = round((float) $order->total * ($rate / 100), 2);

        return Commission::updateOrCreate(
            ['order_id' => $order->id],
            [
                'user_id' => $commercial->id,
                'rate' => $rate,
                'amount' => $amount,
                'status' => 'due',
            ]
        );
    }

    public function revokeForOrder(Order $order): void
    {
        Commission::where('order_id', $order->id)->delete();
    }

    public function syncAfterStatusChange(Order $order, OrderStatus $previousStatus, OrderStatus $newStatus): void
    {
        if ($newStatus === OrderStatus::Livree) {
            $this->grantForDeliveredOrder($order);

            return;
        }

        if ($previousStatus === OrderStatus::Livree) {
            $this->revokeForOrder($order);
        }
    }

    public function rateForCommercial(User $commercial): float
    {
        if ($commercial->commission_rate !== null) {
            return (float) $commercial->commission_rate;
        }

        return (float) Setting::get('commission_rate', 5);
    }

    public function amountForOrder(Order $order, User $commercial): float
    {
        $rate = $this->rateForCommercial($commercial);

        return round((float) $order->total * ($rate / 100), 2);
    }

    public function syncAllDeliveredOrders(): int
    {
        $synced = 0;

        Order::query()
            ->where('status', OrderStatus::Livree)
            ->whereNotNull('commercial_id')
            ->each(function (Order $order) use (&$synced) {
                if ($this->grantForDeliveredOrder($order)) {
                    $synced++;
                }
            });

        return $synced;
    }
}
