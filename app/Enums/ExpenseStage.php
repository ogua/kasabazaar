<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExpenseStage: string implements HasLabel, HasColor
{
    case PreShipment = 'pre_shipment';
    case DuringShipment = 'during_shipment';
    case PostShipment = 'post_shipment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PreShipment => 'Pre-Shipment',
            self::DuringShipment => 'During Shipment',
            self::PostShipment => 'Post-Shipment',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PreShipment => 'warning',
            self::DuringShipment => 'info',
            self::PostShipment => 'success',
        };
    }
}
