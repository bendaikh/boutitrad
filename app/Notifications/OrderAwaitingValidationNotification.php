<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderAwaitingValidationNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing(['commercial', 'client']);

        return [
            'type' => 'order_awaiting_validation',
            'message' => sprintf(
                'Commande %s envoyée par %s — en attente de validation',
                $this->order->reference,
                $this->order->commercial?->name ?? 'Commercial',
            ),
            'order_id' => $this->order->id,
            'order_reference' => $this->order->reference,
            'commercial_name' => $this->order->commercial?->name,
            'client_name' => $this->order->client?->name,
            'total' => (float) $this->order->total,
            'url' => route('orders.bon', $this->order),
        ];
    }
}
