<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Commercial = 'commercial';
    case Livreur = 'livreur';
    case GestionnaireStock = 'gestionnaire_stock';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Commercial => 'Commercial',
            self::Livreur => 'Livreur',
            self::GestionnaireStock => 'Gestionnaire Stock',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'purple',
            self::Commercial => 'blue',
            self::Livreur => 'green',
            self::GestionnaireStock => 'orange',
        };
    }
}
