<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class CathedisConfig
{
    public static function enabled(): bool
    {
        $fromDb = Setting::get('cathedis_enabled');

        return $fromDb !== null && $fromDb !== ''
            ? filter_var($fromDb, FILTER_VALIDATE_BOOLEAN)
            : false;
    }

    public static function username(): ?string
    {
        $value = Setting::get('cathedis_username');

        return filled($value) ? (string) $value : null;
    }

    public static function password(): ?string
    {
        $stored = Setting::get('cathedis_password');
        if (! filled($stored)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $stored);
        } catch (\Throwable) {
            return (string) $stored;
        }
    }

    public static function apiToken(): ?string
    {
        $value = Setting::get('cathedis_api_token');

        return filled($value) ? (string) $value : null;
    }

    public static function isConfigured(): bool
    {
        return filled(self::apiToken())
            || (filled(self::username()) && filled(self::password()));
    }

    public static function storeId(): ?int
    {
        $value = Setting::get('cathedis_store_id');

        return filled($value) && is_numeric($value) ? (int) $value : null;
    }

    public static function defaultSectorId(): ?int
    {
        $value = Setting::get('cathedis_default_sector_id');

        return filled($value) && is_numeric($value) ? (int) $value : null;
    }

    public static function defaultSectorName(): ?string
    {
        $value = Setting::get('cathedis_default_sector_name');

        return filled($value) ? (string) $value : null;
    }

    public static function paymentTypeId(): int
    {
        return (int) (Setting::get('cathedis_payment_type_id') ?: 1);
    }

    public static function deliveryTypeId(): int
    {
        return (int) (Setting::get('cathedis_delivery_type_id') ?: 1);
    }

    public static function deliveryStatusId(): int
    {
        return (int) (Setting::get('cathedis_delivery_status_id') ?: 1);
    }

    public static function deliveryStatusCode(): string
    {
        return (string) (Setting::get('cathedis_delivery_status_code') ?: 'En Attente Ramassage');
    }

    public static function allowOpening(): bool
    {
        return filter_var(Setting::get('cathedis_allow_opening'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function rangeWeight(): string
    {
        return (string) (Setting::get('cathedis_range_weight') ?: 'ONE_FIVE');
    }

    public static function shippingMethod(): string
    {
        return (string) (Setting::get('cathedis_shipping_method') ?: 'LAD');
    }

    public static function typeDelivery(): string
    {
        return (string) (Setting::get('cathedis_type_delivery') ?: 'NORMAL');
    }

    public static function isDispatchReady(): bool
    {
        return self::enabled()
            && self::isConfigured()
            && self::storeId() !== null
            && self::defaultSectorId() !== null
            && filled(self::defaultSectorName());
    }

    /**
     * @return list<string>
     */
    public static function missingDispatchRequirements(): array
    {
        $missing = [];

        if (! self::enabled()) {
            $missing[] = 'API Cathedis non activée';
        }

        if (! self::isConfigured()) {
            $missing[] = 'Identifiants Cathedis (email + mot de passe ou token)';
        }

        if (self::storeId() === null) {
            $missing[] = 'ID magasin Cathedis';
        }

        if (self::defaultSectorId() === null || ! filled(self::defaultSectorName())) {
            $missing[] = 'Secteur par défaut (ID + nom)';
        }

        return $missing;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formValues(): array
    {
        return [
            'enabled' => self::enabled(),
            'username' => self::username(),
            'store_id' => self::storeId(),
            'default_sector_id' => self::defaultSectorId(),
            'default_sector_name' => self::defaultSectorName(),
            'payment_type_id' => self::paymentTypeId(),
            'delivery_type_id' => self::deliveryTypeId(),
            'delivery_status_id' => self::deliveryStatusId(),
            'delivery_status_code' => self::deliveryStatusCode(),
            'allow_opening' => self::allowOpening(),
            'range_weight' => self::rangeWeight(),
            'shipping_method' => self::shippingMethod(),
            'type_delivery' => self::typeDelivery(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function persist(array $data): void
    {
        if (array_key_exists('enabled', $data)) {
            Setting::set('cathedis_enabled', $data['enabled'] ? '1' : '0', 'cathedis');
        }

        if (array_key_exists('username', $data) && filled($data['username'])) {
            Setting::set('cathedis_username', (string) $data['username'], 'cathedis');
        }

        if (array_key_exists('password', $data) && filled($data['password'])) {
            Setting::set('cathedis_password', Crypt::encryptString((string) $data['password']), 'cathedis');
        }

        if (array_key_exists('api_token', $data) && filled($data['api_token'])) {
            Setting::set('cathedis_api_token', (string) $data['api_token'], 'cathedis');
        }

        foreach ([
            'store_id' => 'cathedis_store_id',
            'default_sector_id' => 'cathedis_default_sector_id',
            'default_sector_name' => 'cathedis_default_sector_name',
            'payment_type_id' => 'cathedis_payment_type_id',
            'delivery_type_id' => 'cathedis_delivery_type_id',
            'delivery_status_id' => 'cathedis_delivery_status_id',
            'delivery_status_code' => 'cathedis_delivery_status_code',
            'range_weight' => 'cathedis_range_weight',
            'shipping_method' => 'cathedis_shipping_method',
            'type_delivery' => 'cathedis_type_delivery',
        ] as $key => $settingKey) {
            if (array_key_exists($key, $data) && filled($data[$key])) {
                Setting::set($settingKey, (string) $data[$key], 'cathedis');
            }
        }

        if (array_key_exists('allow_opening', $data)) {
            Setting::set('cathedis_allow_opening', $data['allow_opening'] ? '1' : '0', 'cathedis');
        }
    }
}
