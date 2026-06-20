<?php

namespace App\Enums;

enum Bank: string
{
    case Awb = 'AWB';
    case Bmce = 'BMCE';
    case Bmci = 'BMCI';
    case Cih = 'CIH';
    case Bp = 'BP';
    case Omnia = 'OMNIA';
    case BnqAkhdar = 'BNQ AKHDAR';
    case Esp = 'ESP';
    case CaYoussr = 'CA YOUSSR';
    case Barid = 'BARID';
    case Sg = 'SG';

    public function label(): string
    {
        return $this->value;
    }
}
