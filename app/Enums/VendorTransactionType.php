<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorTransactionType: string implements HasColor, HasLabel
{
    case SaleCredit = 'sale_credit';
    case CommissionFee = 'commission_fee';
    case Payout = 'payout';
    case RefundReversal = 'refund_reversal';
    case Adjustment = 'adjustment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SaleCredit => 'Sale Credit',
            self::CommissionFee => 'Commission Fee',
            self::Payout => 'Payout',
            self::RefundReversal => 'Refund Reversal',
            self::Adjustment => 'Adjustment',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SaleCredit => 'success',
            self::CommissionFee => 'gray',
            self::Payout => 'info',
            self::RefundReversal => 'danger',
            self::Adjustment => 'warning',
        };
    }
}
