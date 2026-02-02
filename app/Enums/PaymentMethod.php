<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel, HasColor
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case MobileMoney = 'mobile_money';
    case Cheque = 'cheque';
    case Card = 'card';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::MobileMoney => 'Mobile Money',
            self::Cheque => 'Cheque',
            self::Card => 'Card',
            self::Other => 'Other',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cash => 'success',
            self::BankTransfer => 'info',
            self::MobileMoney => 'warning',
            self::Cheque => 'gray',
            self::Card => 'primary',
            self::Other => 'secondary',
        };
    }
}
