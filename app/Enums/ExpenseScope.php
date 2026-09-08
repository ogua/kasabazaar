<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExpenseScope: string implements HasColor, HasLabel
{
    case Shipment = 'shipment';
    case Container = 'container';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Shipment => 'Specific Shipment',
            self::Container => 'Whole Container',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Shipment => 'info',
            self::Container => 'warning',
        };
    }
}
