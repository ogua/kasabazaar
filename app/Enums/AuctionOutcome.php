<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AuctionOutcome: string implements HasColor, HasLabel
{
    case Sold = 'sold';
    case ReserveNotMet = 'reserve_not_met';
    case NoBids = 'no_bids';
    // Sold, but every buyer offered the lot let the payment window lapse.
    case Unpaid = 'unpaid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::ReserveNotMet => 'Reserve not met',
            self::NoBids => 'No bids',
            self::Unpaid => 'Unpaid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Sold => 'success',
            self::ReserveNotMet => 'warning',
            self::NoBids => 'gray',
            self::Unpaid => 'danger',
        };
    }
}
