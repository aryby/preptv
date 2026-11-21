<?php

namespace App\Domain\Catalog\Enums;

enum BillingPeriodUnit: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function label(int $count): string
    {
        $unit = match ($this) {
            self::Day => $count === 1 ? 'jour' : 'jours',
            self::Week => $count === 1 ? 'semaine' : 'semaines',
            self::Month => 'mois',
            self::Year => $count === 1 ? 'an' : 'ans',
        };

        return "{$count} {$unit}";
    }
}
