<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The state an item is in when it is listed for sale. The storefront shows the
 * label and the description in a popover, so the wording here is what shoppers
 * read — keep it in step with kmarket's App\Support\ProductCondition.
 */
enum ProductCondition: string implements HasColor, HasLabel
{
    case New = 'new';
    case FactorySealed = 'factory_sealed';
    case Refurbished = 'refurbished';
    case Used = 'used';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::New => 'New',
            self::FactorySealed => 'Factory Sealed',
            self::Refurbished => 'Refurbished',
            self::Used => 'Used',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'success',
            self::FactorySealed => 'info',
            self::Refurbished => 'warning',
            self::Used => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::New => 'Brand new and unused. It may be out of its original packaging, or the packaging may have been opened.',
            self::FactorySealed => 'Brand new and never opened. Still in the manufacturer\'s original, unbroken seal.',
            self::Refurbished => 'Previously owned, then inspected, repaired and restored to working order. It may show light signs of wear.',
            self::Used => 'Previously owned and used. It works, but expect signs of wear. Check the photos and description for details.',
        };
    }
}
