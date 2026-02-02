<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VehicleStatus: string implements HasLabel, HasColor
{
    case Available = 'available';
    case InUse = 'in_use';
    case Maintenance = 'maintenance';
    case Retired = 'retired';
    case OnTrip = 'ontrip';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InUse => 'In Use',
            self::Maintenance => 'Maintenance',
            self::Retired => 'Retired',
            self::OnTrip => 'On Trip',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Available => 'success',
            self::InUse => 'info',
            self::Maintenance => 'warning',
            self::Retired => 'gray',
            self::OnTrip => 'info',
        };
    }
}
