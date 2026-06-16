<?php

namespace App\Models;

use App\Enums\PaymentMode;
use App\Enums\ProspectionSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'facebook_page', 'instagram_page', 'photo',
        'address', 'city', 'city_id', 'prospection', 'payment_mode',
        'commercial_id', 'balance', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'prospection' => ProspectionSource::class,
            'payment_mode' => PaymentMode::class,
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function commercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function cityRecord(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function formattedId(): string
    {
        return 'CL-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? '/storage/'.$this->photo : null;
    }

    public function deliveryCityName(): string
    {
        return $this->cityRecord?->name ?? $this->city ?? '';
    }

    public function suggestedDeliveryCost(): float
    {
        $pack = config('cathedis_cities.default_pack', 'silver');

        if ($this->cityRecord) {
            return $this->cityRecord->deliveryCost($pack);
        }

        $matched = City::findByName($this->city);

        return $matched?->deliveryCost($pack) ?? 0.0;
    }

    public function totalPurchases(): float
    {
        return (float) $this->orders()
            ->whereIn('status', ['confirmee', 'en_preparation', 'expediee', 'livree'])
            ->sum('total');
    }
}
