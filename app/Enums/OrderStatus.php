<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Nouvelle = 'nouvelle';
    case EnCours = 'en_cours';
    case Confirmee = 'confirmee';
    case EnPreparation = 'en_preparation';
    case Expediee = 'expediee';
    case Livree = 'livree';
    case Annulee = 'annulee';
    case Retournee = 'retournee';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::EnCours => 'En cours',
            self::Confirmee => 'Confirmée',
            self::EnPreparation => 'En préparation',
            self::Expediee => 'Expédiée',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
            self::Retournee => 'Retournée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Nouvelle => 'gray',
            self::EnCours => 'blue',
            self::Confirmee => 'indigo',
            self::EnPreparation => 'yellow',
            self::Expediee => 'cyan',
            self::Livree => 'green',
            self::Annulee => 'red',
            self::Retournee => 'orange',
        };
    }

    public static function activeStatuses(): array
    {
        return [
            self::Nouvelle,
            self::EnCours,
            self::Confirmee,
            self::EnPreparation,
            self::Expediee,
        ];
    }
}
