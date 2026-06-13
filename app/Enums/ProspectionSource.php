<?php

namespace App\Enums;

enum ProspectionSource: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Terrain = 'terrain';
    case BoucheAOreille = 'bouche_a_oreille';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Terrain => 'Terrain',
            self::BoucheAOreille => 'Bouche à oreille',
        };
    }
}
