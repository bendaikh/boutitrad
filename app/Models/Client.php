<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'balance', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function totalPurchases(): float
    {
        return (float) $this->orders()
            ->whereIn('status', ['confirmee', 'en_preparation', 'expediee', 'livree'])
            ->sum('total');
    }
}
