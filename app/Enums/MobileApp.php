<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The mobile apps that talk to this backend, each a separate store listing
 * with its own release history.
 */
enum MobileApp: string implements HasColor, HasLabel
{
    case Client = 'client';
    case Staff = 'staff';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Client => 'KASAROSE client app',
            self::Staff => 'Staff app',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Client => 'primary',
            self::Staff => 'info',
        };
    }
}
