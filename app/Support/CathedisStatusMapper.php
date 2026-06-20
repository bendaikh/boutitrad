<?php

namespace App\Support;

use App\Enums\OrderStatus;

class CathedisStatusMapper
{
    public static function normalize(string $status): string
    {
        $status = trim($status);

        if ($status === '') {
            return '';
        }

        $status = mb_strtolower($status, 'UTF-8');
        $status = strtr($status, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', $status) ?? $status;
    }

    public static function map(?string $cathedisStatus): ?OrderStatus
    {
        $normalized = self::normalize((string) $cathedisStatus);

        if ($normalized === '') {
            return null;
        }

        if (self::matches($normalized, ['livre', 'delivered'])) {
            return OrderStatus::Livree;
        }

        if (self::matches($normalized, ['retour', 'return'])) {
            return OrderStatus::Retournee;
        }

        if (self::matches($normalized, ['annul', 'canceled', 'cancelled'])) {
            return OrderStatus::Annulee;
        }

        if (self::matches($normalized, ['confirme', 'confirmed'])) {
            return OrderStatus::Confirmee;
        }

        if (self::matches($normalized, [
            'en cours de livraison',
            'in delivering',
            'in_delivering',
            'expedie',
            'expedition',
            'transit',
            'collecte',
            'collected',
            'ramasse',
            'picked',
        ])) {
            return OrderStatus::Expediee;
        }

        if (self::matches($normalized, [
            'en preparation',
            'preparation',
            'hub',
        ])) {
            return OrderStatus::EnPreparation;
        }

        return null;
    }

    public static function rank(OrderStatus $status): int
    {
        return match ($status) {
            OrderStatus::Nouvelle => 0,
            OrderStatus::EnCours => 1,
            OrderStatus::Confirmee => 2,
            OrderStatus::EnPreparation => 3,
            OrderStatus::Expediee => 4,
            OrderStatus::Livree => 5,
            OrderStatus::Retournee => 90,
            OrderStatus::Annulee => 91,
        };
    }

    public static function isTerminal(OrderStatus $status): bool
    {
        return in_array($status, [OrderStatus::Livree, OrderStatus::Retournee, OrderStatus::Annulee], true);
    }

    public static function shouldApply(OrderStatus $current, OrderStatus $target): bool
    {
        if ($current === $target) {
            return false;
        }

        if (self::isTerminal($target)) {
            return true;
        }

        if (self::isTerminal($current)) {
            return false;
        }

        return self::rank($target) > self::rank($current);
    }

    /**
     * @param  list<string>  $needles
     */
    private static function matches(string $normalizedStatus, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($normalizedStatus, self::normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    public static function isConfirmed(string $cathedisStatus): bool
    {
        return self::matches(self::normalize($cathedisStatus), ['confirme', 'confirmed']);
    }

    public static function displayColor(string $cathedisStatus): string
    {
        $normalized = self::normalize($cathedisStatus);

        if ($normalized === '') {
            return 'gray';
        }

        if (self::matches($normalized, ['confirme', 'confirmed'])) {
            return 'indigo';
        }

        if (self::matches($normalized, ['livre', 'delivered'])) {
            return 'green';
        }

        if (self::matches($normalized, ['retour', 'return'])) {
            return 'orange';
        }

        if (self::matches($normalized, ['annul', 'canceled', 'cancelled'])) {
            return 'red';
        }

        if (self::matches($normalized, ['injoignable', 'errone', 'voyage'])) {
            return 'yellow';
        }

        if (self::matches($normalized, ['attente', 'preparation', 'hub'])) {
            return 'blue';
        }

        if (self::matches($normalized, [
            'en cours',
            'expedie',
            'transit',
            'collecte',
            'collected',
            'ramass',
            'livraison',
        ])) {
            return 'cyan';
        }

        return 'gray';
    }
}
