<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmploymentStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Terminated = 'terminated';
    case OnLeave = 'on_leave';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Terminated => 'Terminated',
            self::OnLeave => 'On Leave',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Terminated => 'danger',
            self::OnLeave => 'warning',
        };
    }
}
