<?php

namespace App\Enums;

enum CityZone: string
{
    case Ramassage = 'ramassage';
    case Grande = 'grande';
    case Petite = 'petite';
    case Sud = 'sud';

    public function label(): string
    {
        return match ($this) {
            self::Ramassage => 'Ville ramassage',
            self::Grande => 'Grande ville',
            self::Petite => 'Petite ville',
            self::Sud => 'Région Sud',
        };
    }

    public function defaultDeliveryCost(string $pack = 'silver'): float
    {
        return match ($this) {
            self::Ramassage => $pack === 'gold' ? 25.0 : 20.0,
            self::Grande => $pack === 'gold' ? 40.0 : 35.0,
            self::Petite => $pack === 'gold' ? 45.0 : 40.0,
            self::Sud => $pack === 'gold' ? 50.0 : 45.0,
        };
    }
}
