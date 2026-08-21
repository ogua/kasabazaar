<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountSubtype: string implements HasLabel
{
    case current_asset = 'current_asset';
    case fixed_asset = 'fixed_asset';
    case current_liability = 'current_liability';
    case long_term_liability = 'long_term_liability';
    case equity = 'equity';
    case revenue = 'revenue';
    case cost_of_sales = 'cost_of_sales';
    case operating_expense = 'operating_expense';
    case finance_cost = 'finance_cost';

    public function type(): AccountType
    {
        return match ($this) {
            self::current_asset, self::fixed_asset => AccountType::asset,
            self::current_liability, self::long_term_liability => AccountType::liability,
            self::equity => AccountType::equity,
            self::revenue => AccountType::income,
            self::cost_of_sales, self::operating_expense, self::finance_cost => AccountType::expense,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::current_asset => 'Current Asset',
            self::fixed_asset => 'Fixed Asset',
            self::current_liability => 'Current Liability',
            self::long_term_liability => 'Long-Term Liability',
            self::equity => 'Equity',
            self::revenue => 'Revenue',
            self::cost_of_sales => 'Cost of Sales',
            self::operating_expense => 'Operating Expense',
            self::finance_cost => 'Finance Cost',
        };
    }
}
