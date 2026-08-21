<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentInterestPayoutStatus: string implements HasColor, HasLabel
{
    case due = 'due';
    case processing = 'processing';
    case paid = 'paid';
    case skipped = 'skipped';
    case reversed = 'reversed';

    /**
     * Earned but never paid in cash — rolled into the principal of a successor
     * tranche by a capital conversion. Unlike 'skipped' (never owed) or
     * 'reversed' (cash left and was clawed back), this payout was genuinely
     * earned, so it still counts toward interest accrued; it just is no longer
     * owed as cash.
     */
    case converted = 'converted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::due => 'Due',
            self::processing => 'Processing',
            self::paid => 'Paid',
            self::skipped => 'Skipped',
            self::reversed => 'Reversed',
            self::converted => 'Converted to Principal',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::due => 'gray',
            self::processing => 'warning',
            self::paid => 'success',
            self::skipped => 'danger',
            self::reversed => 'danger',
            self::converted => 'info',
        };
    }
}
