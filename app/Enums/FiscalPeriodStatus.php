<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FiscalPeriodStatus: string implements HasColor, HasLabel
{
    case open = 'open';
    case closed = 'closed';
    case locked = 'locked';

    /** A locked year's balances may not be edited — it has been filed or audited. */
    public function isEditable(): bool
    {
        return $this !== self::locked;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::open => 'Open',
            self::closed => 'Closed',
            self::locked => 'Locked',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::open => 'success',
            self::closed => 'warning',
            self::locked => 'gray',
        };
    }
}
