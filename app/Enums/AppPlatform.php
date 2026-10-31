<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The store a mobile app build is published to.
 */
enum AppPlatform: string implements HasColor, HasLabel
{
    case Android = 'android';
    case Ios = 'ios';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iOS',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Android => 'success',
            self::Ios => 'gray',
        };
    }
}
