<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ShipmentRequestStatus: string implements HasColor, HasLabel
{
    case submitted = 'submitted';
    case under_review = 'under_review';
    case approved = 'approved';
    case rejected = 'rejected';
    case cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::submitted => 'Submitted',
            self::under_review => 'Under Review',
            self::approved => 'Approved',
            self::rejected => 'Rejected',
            self::cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::submitted => 'gray',
            self::under_review => 'info',
            self::approved => 'success',
            self::rejected => 'danger',
            self::cancelled => 'warning',
        };
    }
}
