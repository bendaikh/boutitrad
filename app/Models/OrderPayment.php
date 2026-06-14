<?php

namespace App\Models;

use App\Enums\PaymentMode;
use App\Enums\RegulationStatus;
use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_date',
        'payment_mode',
        'bank',
        'payment_number',
        'settlement_status',
        'regulation_status',
        'drawer_name',
        'encashment_date',
        'treasury_id',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'encashment_date' => 'date',
            'payment_mode' => PaymentMode::class,
            'settlement_status' => SettlementStatus::class,
            'regulation_status' => RegulationStatus::class,
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function regulationStatus(): RegulationStatus
    {
        if ($this->regulation_status) {
            return $this->regulation_status;
        }

        return $this->computedRegulationStatus();
    }

    protected function computedRegulationStatus(): RegulationStatus
    {
        $order = $this->order;

        if ($order->balanceDue() <= 0) {
            return RegulationStatus::Paye;
        }

        if ($this->isPendingEncashment()) {
            return RegulationStatus::EnInstance;
        }

        if ($order->paidAmount() > 0) {
            return RegulationStatus::EnCours;
        }

        return RegulationStatus::Impaye;
    }

    protected function isPendingEncashment(): bool
    {
        if ($this->encashment_date !== null) {
            return false;
        }

        return in_array($this->payment_mode, [
            PaymentMode::Chq,
            PaymentMode::Eff,
            PaymentMode::Vir,
        ], true);
    }
}
