<?php

namespace App\Models;

use App\Enums\Bank;
use App\Enums\ChargeType;
use App\Enums\ExpenseTreasuryMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'charge_type',
        'amount',
        'treasury_mode',
        'payment_number',
        'bank',
        'drawer_name',
        'instrument_date',
        'category',
        'expense_date',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'instrument_date' => 'date',
            'charge_type' => ChargeType::class,
            'treasury_mode' => ExpenseTreasuryMode::class,
            'bank' => Bank::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCaissePayment(): bool
    {
        return ($this->treasury_mode ?? ExpenseTreasuryMode::Caisse) === ExpenseTreasuryMode::Caisse;
    }
}
