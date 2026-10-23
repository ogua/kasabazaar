<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ShipmentPaymentAttemptStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Abandoned = 'abandoned';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Success => 'Paid',
            self::Failed => 'Failed',
            self::Abandoned => 'Not completed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Success => 'success',
            self::Failed => 'danger',
            self::Abandoned => 'gray',
        };
    }
}
