<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasColor, HasLabel
{
    case asset = 'asset';
    case liability = 'liability';
    case equity = 'equity';
    case income = 'income';
    case expense = 'expense';

    /**
     * Whether a positive balance on this account is a debit. Assets and expenses
     * increase on the debit side; liabilities, equity and income on the credit side.
     * Used to check that a manually keyed trial balance actually balances.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this, [self::asset, self::expense], true);
    }

    /** Accounts that appear on the balance sheet rather than the P&L. */
    public function isBalanceSheet(): bool
    {
        return in_array($this, [self::asset, self::liability, self::equity], true);
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::asset => 'Asset',
            self::liability => 'Liability',
            self::equity => 'Equity',
            self::income => 'Income',
            self::expense => 'Expense',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::asset => 'success',
            self::liability => 'warning',
            self::equity => 'info',
            self::income => 'success',
            self::expense => 'danger',
        };
    }
}
