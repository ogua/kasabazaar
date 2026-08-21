<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentConversionSourceMode: string implements HasColor, HasLabel
{
    /** Principal plus all accrued interest rolls into the successor; the source closes. */
    case full = 'full';

    /** Only the principal rolls; accrued interest is paid out to the investor as cash. */
    case principal_only = 'principal_only';

    /** A staff/investor-specified amount rolls; the source stays open with the remainder. */
    case partial = 'partial';

    /**
     * Whether this mode settles the source tranche completely. Only a full roll
     * closes it — the other two leave a live balance behind.
     */
    public function closesSource(): bool
    {
        return $this === self::full;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::full => 'Full (principal + interest)',
            self::principal_only => 'Principal only (interest paid out)',
            self::partial => 'Partial amount',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::full => 'success',
            self::principal_only => 'info',
            self::partial => 'warning',
        };
    }
}
