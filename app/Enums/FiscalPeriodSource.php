<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FiscalPeriodSource: string implements HasColor, HasLabel
{
    /**
     * Balances keyed in by hand from the accountant's or auditor's books. Used for
     * years that predate this system holding any transactions.
     */
    case manual = 'manual';

    /** Balances computed from live cashbook, shipment, expense and investment data. */
    case derived = 'derived';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::manual => 'Manually Entered',
            self::derived => 'Derived from Records',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::manual => 'warning',
            self::derived => 'success',
        };
    }
}
