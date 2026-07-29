<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentCapitalType: string implements HasColor, HasLabel
{
    case investment = 'investment';
    case loan = 'loan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::investment => 'Investment',
            self::loan => 'Loan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::investment => 'info',
            self::loan => 'warning',
        };
    }
}
