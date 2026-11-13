<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Buyer membership categories, lowest to highest. The rank decides which
 * exclusive auctions a member may bid in.
 */
enum MembershipTier: string implements HasLabel
{
    case Basic = 'basic';
    case Standard = 'standard';
    case Economic = 'economic';
    case Executive = 'executive';

    public function rank(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Standard => 2,
            self::Economic => 3,
            self::Executive => 4,
        };
    }

    public function getLabel(): ?string
    {
        return ucfirst($this->value);
    }
}
