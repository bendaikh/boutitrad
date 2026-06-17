<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $min = $this->product->min_quantity ?: 5;

        return [
            'type' => 'low_stock',
            'message' => sprintf(
                'Stock faible : %s — %d unité(s) restante(s) (minimum %d)',
                $this->product->name,
                $this->product->quantity,
                $min,
            ),
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'quantity' => $this->product->quantity,
            'min_quantity' => $min,
            'url' => route('products.index', ['search' => $this->product->sku]),
        ];
    }
}
