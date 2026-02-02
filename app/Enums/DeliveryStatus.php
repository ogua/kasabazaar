<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Partial = 'partial';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Partial => 'Partial',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Delivered => 'success',
            self::Failed => 'danger',
            self::Partial => 'info',
        };
    }
}
