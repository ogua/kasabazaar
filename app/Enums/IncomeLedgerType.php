<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IncomeLedgerType: string implements HasLabel, HasColor
{
    case Construction = 'construction';
    case Shipping = 'shipping';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Construction => 'Construction Income',
            self::Shipping => 'Shipping Fee',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Construction => 'warning',
            self::Shipping => 'info',
        };
    }

    /**
     * The CashbookCostCenter cases that feed this ledger.
     */
    public function costCenters(): array
    {
        return match ($this) {
            self::Construction => [CashbookCostCenter::Sales->value],
            self::Shipping => [CashbookCostCenter::ShippingFee->value],
        };
    }
}
