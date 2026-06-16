<?php

namespace App\Models;

use App\Enums\CityZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cathedis_code',
        'zone',
        'delivery_cost_silver',
        'delivery_cost_gold',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'zone' => CityZone::class,
            'delivery_cost_silver' => 'decimal:2',
            'delivery_cost_gold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function deliveryCost(string $pack = 'silver'): float
    {
        $stored = $pack === 'gold' ? $this->delivery_cost_gold : $this->delivery_cost_silver;

        return (float) ($stored ?? $this->zone->defaultDeliveryCost($pack));
    }

    public static function upsertFromPayload(array $payload): self
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $code = $payload['cathedis_code'] ?? $payload['code'] ?? $payload['id'] ?? null;
        $slug = Str::slug($name);

        $zone = CityZone::tryFrom((string) ($payload['zone'] ?? '')) ?? CityZone::Petite;

        return static::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'cathedis_code' => $code ? (string) $code : null,
                'zone' => $zone,
                'delivery_cost_silver' => $payload['delivery_cost_silver'] ?? $zone->defaultDeliveryCost('silver'),
                'delivery_cost_gold' => $payload['delivery_cost_gold'] ?? $zone->defaultDeliveryCost('gold'),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'sort_order' => (int) ($payload['sort_order'] ?? 100),
            ],
        );
    }

    public static function findByName(?string $name): ?self
    {
        if (! $name) {
            return null;
        }

        $slug = Str::slug($name);

        return static::query()
            ->where('slug', $slug)
            ->orWhere('name', $name)
            ->first();
    }
}
