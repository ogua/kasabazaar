<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentTransactionType: string implements HasColor, HasLabel
{
    case contribution = 'contribution';
    case interest_credit = 'interest_credit';
    case withdrawal = 'withdrawal';
    case adjustment = 'adjustment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::contribution => 'Contribution',
            self::interest_credit => 'Interest Credit',
            self::withdrawal => 'Withdrawal',
            self::adjustment => 'Adjustment',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::contribution => 'success',
            self::interest_credit => 'info',
            self::withdrawal => 'danger',
            self::adjustment => 'gray',
        };
    }
}
