<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ShippingStatus : string implements HasLabel, HasColor
{
    case pending = 'pending';
    case intransit = 'in transit';
    case delivered = 'delivered';
    case cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string | array | null {

        return match ($this){
            self::pending => 'warning',
            self::intransit => 'info',
            self::delivered => 'success',
            self::cancelled => 'danger',
        };

    }
}
