<?php

namespace App\Enums;

enum ChargeType: string
{
    case Loyer = 'loyer';
    case Salaire = 'salaire';
    case Transport = 'transport';
    case Marketing = 'marketing';
    case Fournitures = 'fournitures';
    case Communication = 'communication';
    case Entretien = 'entretien';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Loyer => 'Loyer',
            self::Salaire => 'Salaire',
            self::Transport => 'Transport',
            self::Marketing => 'Marketing',
            self::Fournitures => 'Fournitures',
            self::Communication => 'Communication',
            self::Entretien => 'Entretien',
            self::Autre => 'Autre',
        };
    }
}
