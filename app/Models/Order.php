<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference', 'client_id', 'commercial_id', 'livreur_id', 'status',
        'subtotal', 'discount', 'delivery_cost', 'tax', 'total', 'amount_paid', 'payment_mode', 'notes', 'internal_notes',
        'validated_at', 'delivered_at', 'cancelled_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'payment_mode' => PaymentMode::class,
            'validated_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function commission(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public static function generateReference(): string
    {
        $prefix = 'CMD-'.now()->format('Ymd');
        $last = static::where('reference', 'like', $prefix.'%')->count();

        return $prefix.'-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    public function orderAmount(): float
    {
        return round((float) $this->total, 2);
    }

    public function paidAmount(): float
    {
        return round((float) $this->amount_paid, 2);
    }

    public function balanceDue(): float
    {
        $total = $this->orderAmount();
        $paid = $this->paidAmount();

        if ($paid <= 0) {
            return $total;
        }

        return max(0, round($total - $paid, 2));
    }

    public function paymentStatus(): string
    {
        $total = $this->orderAmount();
        $paid = $this->paidAmount();

        if ($total <= 0 || $paid >= $total) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function paymentStatusLabel(): string
    {
        return $this->paymentStatus() === 'paid' ? 'Payé' : 'Impayé';
    }
}
