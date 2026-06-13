<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Especes = 'especes';
    case Cheque = 'cheque';
    case Virement = 'virement';
    case Credit = 'credit';
    case Carte = 'carte';

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Cheque => 'Chèque',
            self::Virement => 'Virement',
            self::Credit => 'Crédit',
            self::Carte => 'Carte bancaire',
        };
    }
}
