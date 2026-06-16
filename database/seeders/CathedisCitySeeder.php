<?php

namespace Database\Seeders;

use App\Enums\CityZone;
use App\Models\City;
use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CathedisCitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('cathedis_cities.cities', []) as $row) {
            $zone = CityZone::tryFrom($row['zone'] ?? 'petite') ?? CityZone::Petite;

            City::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'name' => $row['name'],
                    'cathedis_code' => $row['cathedis_code'] ?? null,
                    'zone' => $zone,
                    'delivery_cost_silver' => $row['delivery_cost_silver'] ?? $zone->defaultDeliveryCost('silver'),
                    'delivery_cost_gold' => $row['delivery_cost_gold'] ?? $zone->defaultDeliveryCost('gold'),
                    'is_active' => true,
                    'sort_order' => $row['sort_order'] ?? 100,
                ],
            );
        }

        Client::query()->whereNull('city_id')->whereNotNull('city')->each(function (Client $client) {
            $city = City::findByName($client->city);

            if ($city) {
                $client->update(['city_id' => $city->id]);
            }
        });
    }
}
