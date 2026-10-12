<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle status set by staff and by the close job. Whether a published
 * auction is "upcoming" or "live" is derived from its schedule — see
 * Auction::state() — so it never needs a status write to go live.
 */
enum AuctionStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Ended => 'Ended',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Ended => 'info',
            self::Cancelled => 'danger',
        };
    }
}
