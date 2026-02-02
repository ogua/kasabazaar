<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PayrollStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Processing => 'Processing',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Processing => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }
}
