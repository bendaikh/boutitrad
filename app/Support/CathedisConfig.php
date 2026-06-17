<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class CathedisConfig
{
    public static function enabled(): bool
    {
        $fromDb = Setting::get('cathedis_enabled');
        if ($fromDb !== null && $fromDb !== '') {
            return filter_var($fromDb, FILTER_VALIDATE_BOOLEAN);
        }

        return filter_var(config('cathedis.enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function username(): ?string
    {
        $fromDb = Setting::get('cathedis_username');
        if (filled($fromDb)) {
            return (string) $fromDb;
        }

        $value = config('cathedis.username');

        return filled($value) ? (string) $value : null;
    }

    public static function password(): ?string
    {
        $stored = Setting::get('cathedis_password');
        if (filled($stored)) {
            try {
                return Crypt::decryptString((string) $stored);
            } catch (\Throwable) {
                return (string) $stored;
            }
        }

        $value = config('cathedis.password');

        return filled($value) ? (string) $value : null;
    }

    public static function apiToken(): ?string
    {
        $fromDb = Setting::get('cathedis_api_token');
        if (filled($fromDb)) {
            return (string) $fromDb;
        }

        $value = config('cathedis.api_token');

        return filled($value) ? (string) $value : null;
    }

    public static function isConfigured(): bool
    {
        return filled(self::apiToken())
            || (filled(self::username()) && filled(self::password()));
    }

    public static function storeId(): int
    {
        $fromDb = Setting::get('cathedis_store_id');
        if (filled($fromDb) && is_numeric($fromDb)) {
            return (int) $fromDb;
        }

        return (int) config('cathedis.store_id', 23055);
    }

    public static function syncFromEnv(): void
    {
        if (filter_var(config('cathedis.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            Setting::set('cathedis_enabled', '1', 'cathedis');
        }

        if (filled(config('cathedis.username'))) {
            Setting::set('cathedis_username', (string) config('cathedis.username'), 'cathedis');
        }

        if (filled(config('cathedis.password'))) {
            Setting::set('cathedis_password', Crypt::encryptString((string) config('cathedis.password')), 'cathedis');
        }

        if (filled(config('cathedis.api_token'))) {
            Setting::set('cathedis_api_token', (string) config('cathedis.api_token'), 'cathedis');
        }
    }

    /**
     * @param  array{username?: ?string, password?: ?string, api_token?: ?string, enabled?: bool}  $data
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
    }
}
