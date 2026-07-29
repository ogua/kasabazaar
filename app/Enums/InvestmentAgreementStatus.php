<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentAgreementStatus: string implements HasColor, HasLabel
{
    case unsigned = 'unsigned';
    case pending_review = 'pending_review';
    case finalized = 'finalized';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::unsigned => 'Not Signed',
            self::pending_review => 'Pending Review',
            self::finalized => 'Finalized',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::unsigned => 'gray',
            self::pending_review => 'warning',
            self::finalized => 'success',
        };
    }
}
