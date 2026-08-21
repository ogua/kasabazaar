<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentConversionStatus: string implements HasColor, HasLabel
{
    case pending_approval = 'pending_approval';
    case approved = 'approved';
    case executed = 'executed';
    case rejected = 'rejected';
    case cancelled = 'cancelled';

    /**
     * Whether the conversion may still be executed. Staff-initiated conversions
     * are created already approved and executed in the same request; an
     * investor-raised request has to be approved first.
     */
    public function isExecutable(): bool
    {
        return $this === self::approved;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::pending_approval => 'Pending Approval',
            self::approved => 'Approved',
            self::executed => 'Executed',
            self::rejected => 'Rejected',
            self::cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::pending_approval => 'warning',
            self::approved => 'info',
            self::executed => 'success',
            self::rejected => 'danger',
            self::cancelled => 'gray',
        };
    }
}
