<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IncomeStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }
}
