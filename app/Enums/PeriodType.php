<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PeriodType: string implements HasLabel, HasColor
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Monthly => 'info',
            self::Quarterly => 'warning',
            self::Yearly => 'success',
        };
    }
}
