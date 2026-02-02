<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PayrollEntryStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
        };
    }
}
