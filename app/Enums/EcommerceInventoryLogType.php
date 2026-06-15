<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EcommerceInventoryLogType: string implements HasColor, HasLabel
{
    case Increase = 'increase';
    case Decrease = 'decrease';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Increase => 'Increase',
            self::Decrease => 'Decrease',
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Return => 'Return',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Increase => 'success',
            self::Decrease => 'danger',
            self::Adjustment => 'warning',
            self::Sale => 'info',
            self::Return => 'gray',
        };
    }
}
