<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AlertSeverity: string implements HasLabel, HasColor
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Warning',
            self::Critical => 'Critical',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warning',
            self::Critical => 'danger',
        };
    }
}
