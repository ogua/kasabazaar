<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentStatus: string implements HasColor, HasLabel
{
    case pending_payment = 'pending_payment';
    case active = 'active';
    case withdrawn = 'withdrawn';
    case closed = 'closed';

    /**
     * Settled into a successor tranche of the other capital type — deliberately
     * distinct from withdrawn/closed, which both mean money went back to the
     * investor. Converted capital is still held by the company, just under a
     * different instrument, so no report may treat it as returned.
     */
    case converted = 'converted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::pending_payment => 'Pending Payment',
            self::active => 'Active',
            self::withdrawn => 'Withdrawn',
            self::closed => 'Closed',
            self::converted => 'Converted',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::pending_payment => 'warning',
            self::active => 'success',
            self::withdrawn => 'gray',
            self::closed => 'danger',
            self::converted => 'info',
        };
    }
}
