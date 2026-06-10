<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Entree = 'entree';
    case Sortie = 'sortie';
    case Ajustement = 'ajustement';
    case Inventaire = 'inventaire';

    public function label(): string
    {
        return match ($this) {
            self::Entree => 'Entrée',
            self::Sortie => 'Sortie',
            self::Ajustement => 'Ajustement',
            self::Inventaire => 'Inventaire',
        };
    }
}
