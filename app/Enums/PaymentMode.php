<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Esp = 'esp';
    case Vir = 'vir';
    case Vers = 'vers';
    case Chq = 'chq';
    case Eff = 'eff';
    case Autre = 'autre';
    case Comptant = 'comptant';
    case Especes = 'especes';
    case Cheque = 'cheque';
    case Virement = 'virement';
    case Credit = 'credit';
    case Carte = 'carte';

    public function label(): string
    {
        return match ($this) {
            self::Esp => 'ESP',
            self::Vir => 'VIR',
            self::Vers => 'VERS',
            self::Chq => 'CHQ',
            self::Eff => 'EFF',
            self::Autre => 'AUTRE',
            self::Comptant => 'COMPTANT',
            self::Especes => 'Espèces',
            self::Cheque => 'Chèque',
            self::Virement => 'Virement',
            self::Credit => 'Crédit',
            self::Carte => 'Carte bancaire',
        };
    }

    public static function forPaymentForm(?self $mode): ?self
    {
        return match ($mode) {
            self::Especes => self::Esp,
            self::Virement => self::Vir,
            self::Cheque => self::Chq,
            default => $mode,
        };
    }
}
