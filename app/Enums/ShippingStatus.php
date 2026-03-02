<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ShippingStatus : string implements HasLabel, HasColor
{
    case pickup = 'pickup';
    case shipped = 'Shipped';
    case delivered = 'delivered';
    case cancelled = 'cancelled';
    case pending = 'pending';
    case cleared = 'cleared';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string | array | null {

        return match ($this){
            self::pickup => 'warning',
            self::shipped => 'info',
            self::delivered => 'success',
            self::cancelled => 'danger',
            self::pending => 'danger',
            self::cleared => 'success',
        };

    }
}
