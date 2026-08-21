<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxFilingType: string implements HasLabel
{
    case corporate_income_tax = 'corporate_income_tax';
    case vat = 'vat';
    case paye = 'paye';
    case withholding_tax = 'withholding_tax';
    case ssnit = 'ssnit';
    case audited_accounts = 'audited_accounts';
    case other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::corporate_income_tax => 'Corporate Income Tax Return',
            self::vat => 'VAT Return',
            self::paye => 'PAYE Return',
            self::withholding_tax => 'Withholding Tax Return',
            self::ssnit => 'SSNIT Return',
            self::audited_accounts => 'Audited Accounts',
            self::other => 'Other Filing',
        };
    }
}
