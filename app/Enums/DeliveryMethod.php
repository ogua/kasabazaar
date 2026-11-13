<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethod: string implements HasLabel
{
    /** Vendor delivers within Ghana. */
    case Delivery = 'delivery';

    /** Buyer collects from the vendor. */
    case Pickup = 'pickup';

    /** KASAROSE ships the item from the USA to Ghana. */
    case KasaroseShipping = 'kasarose_shipping';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Delivery => 'Delivery',
            self::Pickup => 'Pickup',
            self::KasaroseShipping => 'KASAROSE Shipping (USA to Ghana)',
        };
    }
}
