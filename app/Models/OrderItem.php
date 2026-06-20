<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'product_name', 'product_image', 'quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productImageUrl(): ?string
    {
        if ($this->product_image) {
            return '/storage/'.$this->product_image;
        }

        return $this->product?->imageUrl();
    }

    public function hasOrderProductPhoto(): bool
    {
        return filled($this->product_image);
    }
}
