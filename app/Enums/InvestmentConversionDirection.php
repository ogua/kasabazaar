<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentConversionDirection: string implements HasColor, HasLabel
{
    case to_loan = 'to_loan';
    case to_investment = 'to_investment';

    /**
     * The capital type the successor tranche is issued as.
     */
    public function targetCapitalType(): InvestmentCapitalType
    {
        return match ($this) {
            self::to_loan => InvestmentCapitalType::loan,
            self::to_investment => InvestmentCapitalType::investment,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::to_loan => 'Investment → Loan',
            self::to_investment => 'Loan → Investment',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::to_loan => 'warning',
            self::to_investment => 'info',
        };
    }
}
