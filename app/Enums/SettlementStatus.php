<?php

namespace App\Enums;

enum SettlementStatus: string
{
    case Perso = 'perso';
    case Ste = 'ste';

    public function label(): string
    {
        return match ($this) {
            self::Perso => 'PERSO',
            self::Ste => 'STE',
        };
    }
}
