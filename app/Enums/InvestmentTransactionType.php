<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentTransactionType: string implements HasColor, HasLabel
{
    case contribution = 'contribution';
    case interest_credit = 'interest_credit';
    case interest_payout = 'interest_payout';
    case withdrawal = 'withdrawal';
    case adjustment = 'adjustment';

    /**
     * The two halves of a capital conversion: capital leaving a source tranche
     * and the same capital arriving in its successor. Always created as a pair
     * sharing a reference_id, so a statement can tie one to the other.
     */
    case conversion_out = 'conversion_out';
    case conversion_in = 'conversion_in';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::contribution => 'Contribution',
            self::interest_credit => 'Interest Credit',
            self::interest_payout => 'Interest Payout',
            self::withdrawal => 'Withdrawal',
            self::adjustment => 'Adjustment',
            self::conversion_out => 'Converted Out',
            self::conversion_in => 'Converted In',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::contribution => 'success',
            self::interest_credit => 'info',
            self::interest_payout => 'warning',
            self::withdrawal => 'danger',
            self::adjustment => 'gray',
            self::conversion_out => 'warning',
            self::conversion_in => 'success',
        };
    }
}
