<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialObjective extends Model
{
    protected $fillable = [
        'user_id', 'target_amount', 'achieved_amount', 'period_start', 'period_end',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'achieved_amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(100, ($this->achieved_amount / $this->target_amount) * 100);
    }
}
