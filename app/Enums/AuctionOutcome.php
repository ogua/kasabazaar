<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AuctionOutcome: string implements HasColor, HasLabel
{
    case Sold = 'sold';
    case ReserveNotMet = 'reserve_not_met';
    case NoBids = 'no_bids';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Sold => 'Sold',
            self::ReserveNotMet => 'Reserve not met',
            self::NoBids => 'No bids',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Sold => 'success',
            self::ReserveNotMet => 'warning',
            self::NoBids => 'gray',
        };
    }
}
