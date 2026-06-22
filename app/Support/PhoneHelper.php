<?php

namespace App\Support;

class PhoneHelper
{
    public static function digitsOnly(?string $phone): string
    {
        return preg_replace('/\D/', '', (string) $phone) ?? '';
    }

    public static function hasTenDigits(?string $phone): bool
    {
        return strlen(self::digitsOnly($phone)) === 10;
    }

    public static function normalize(?string $phone): ?string
    {
        $digits = self::digitsOnly($phone);

        return $digits !== '' ? $digits : null;
    }
}
