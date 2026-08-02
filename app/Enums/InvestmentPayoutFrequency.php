<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentPayoutFrequency: string implements HasColor, HasLabel
{
    case monthly = 'monthly';
    case quarterly = 'quarterly';
    case semi_annual = 'semi_annual';
    case annual = 'annual';

    public function months(): int
    {
        return match ($this) {
            self::monthly => 1,
            self::quarterly => 3,
            self::semi_annual => 6,
            self::annual => 12,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::monthly => 'Monthly',
            self::quarterly => 'Quarterly',
            self::semi_annual => 'Semi-Annual',
            self::annual => 'Annual',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::monthly => 'gray',
            self::quarterly => 'info',
            self::semi_annual => 'warning',
            self::annual => 'success',
        };
    }
}
