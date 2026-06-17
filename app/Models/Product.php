<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'brand_id', 'name', 'sku', 'barcode', 'supplier', 'city', 'description', 'image',
        'purchase_price', 'sale_price', 'quantity', 'min_quantity', 'unit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->min_quantity;
    }

    public function stockStatus(): string
    {
        if ($this->quantity <= 0) {
            return 'rupture';
        }

        if ($this->quantity <= $this->min_quantity) {
            return 'faible';
        }

        return 'dispo';
    }

    public function stockStatusLabel(): string
    {
        return match ($this->stockStatus()) {
            'dispo' => 'Dispo',
            'faible' => 'Faible',
            'rupture' => 'Rupture',
        };
    }

    public function stockValue(): float
    {
        return (float) ($this->quantity * $this->purchase_price);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? '/storage/'.$this->image : null;
    }

    public function formattedSku(): string
    {
        return $this->sku;
    }

    public static function generateSku(): string
    {
        $max = static::query()
            ->where('sku', 'like', 'PR%')
            ->pluck('sku')
            ->map(function (string $sku) {
                if (preg_match('/^PR(\d+)$/', $sku, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return 'PR'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
