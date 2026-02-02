<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TripStatus: string implements HasLabel, HasColor
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Delayed = 'delayed';
    case Loading = "loading";
    case Planned = "planned";

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Delayed => 'Delayed',
            self::Loading => 'Loading',
            self::Planned => 'Planned',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Delayed => 'danger',
            self::Loading => 'warning',
            self::Planned => 'info',
            default => null,
        };
    }
}
