<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialPayroll extends Model
{
    protected $fillable = [
        'reference',
        'payment_date',
        'pay_month',
        'commercial_id',
        'sales_count',
        'revenue',
        'commission_rate',
        'commission_amount',
        'amount_to_pay',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'sales_count' => 'integer',
            'revenue' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'amount_to_pay' => 'decimal:2',
        ];
    }

    public function commercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payMonthLabel(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->pay_month)
            ->locale('fr')
            ->translatedFormat('F Y');
    }

    public static function generateReference(): string
    {
        $year = (int) now()->format('Y');
        $prefix = 'PAIE-'.$year;

        $latestSequence = static::query()
            ->where('reference', 'like', $prefix.'%')
            ->pluck('reference')
            ->map(fn (string $reference) => self::parseReferenceSequence($reference, $year))
            ->filter()
            ->max();

        $next = ((int) $latestSequence) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function previewReference(): string
    {
        return static::generateReference();
    }

    private static function parseReferenceSequence(string $reference, int $year): ?int
    {
        if (preg_match('/^PAIE-'.$year.'(\d{4})$/', $reference, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
