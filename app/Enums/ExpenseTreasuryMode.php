<?php

namespace App\Enums;

enum ExpenseTreasuryMode: string
{
    case Caisse = 'caisse';
    case Vir = 'vir';
    case Chq = 'chq';
    case Vers = 'vers';

    public function label(): string
    {
        return match ($this) {
            self::Caisse => 'Caisse',
            self::Vir => 'Vir',
            self::Chq => 'Chq',
            self::Vers => 'Vers',
        };
    }

    public function requiresInstrumentDetails(): bool
    {
        return $this !== self::Caisse;
    }
}
