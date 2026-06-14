<?php

namespace App\Enums;

enum RegulationStatus: string
{
    case Paye = 'paye';
    case EnInstance = 'en_instance';
    case EnCours = 'en_cours';
    case Impaye = 'impaye';

    public function label(): string
    {
        return match ($this) {
            self::Paye => 'Payé',
            self::EnInstance => 'En instance',
            self::EnCours => 'En cours',
            self::Impaye => 'Impayé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paye => 'green',
            self::EnInstance => 'yellow',
            self::EnCours => 'blue',
            self::Impaye => 'red',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->color()) {
            'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
            'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
            'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            'red' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        };
    }
}
