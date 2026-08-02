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

    public function getLabel(): ?string
    {
        return match ($this) {
            self::due => 'Due',
            self::processing => 'Processing',
            self::paid => 'Paid',
            self::skipped => 'Skipped',
            self::reversed => 'Reversed',
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
        };
    }
}
