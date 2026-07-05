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

    public function getLabel(): ?string
    {
        return match ($this) {
            self::pending_payment => 'Pending Payment',
            self::active => 'Active',
            self::withdrawn => 'Withdrawn',
            self::closed => 'Closed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::pending_payment => 'warning',
            self::active => 'success',
            self::withdrawn => 'gray',
            self::closed => 'danger',
        };
    }
}
