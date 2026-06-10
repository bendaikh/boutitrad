<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("setting.{$key}");
    }

    public static function company(): array
    {
        return [
            'name' => static::get('company_name', 'BoutiTrad'),
            'email' => static::get('company_email', ''),
            'phone' => static::get('company_phone', ''),
            'address' => static::get('company_address', ''),
            'logo' => static::get('company_logo', ''),
        ];
    }
}
