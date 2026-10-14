<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ExpenseScope: string implements HasColor, HasDescription, HasLabel
{
    case Shipment = 'shipment';
    case Container = 'container';
    case General = 'general';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Shipment => 'Specific Shipment',
            self::Container => 'Whole Container',
            self::General => 'General / Overhead',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Shipment => 'A cost incurred for one shipment.',
            self::Container => 'A cost shared by a whole container, e.g. demurrage or clearing.',
            self::General => 'A running cost not tied to any shipment, e.g. rent, utilities, internet, stationery.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Shipment => 'info',
            self::Container => 'warning',
            self::General => 'gray',
        };
    }

    /**
     * Whether expenses in this scope are tied to the shipping workflow and so
     * carry an expense stage (pre-shipment, delivery, ...).
     */
    public function hasStage(): bool
    {
        return $this !== self::General;
    }
}
