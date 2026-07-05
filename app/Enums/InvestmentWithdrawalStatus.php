<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentWithdrawalStatus: string implements HasColor, HasLabel
{
    case submitted = 'submitted';
    case under_review = 'under_review';
    case approved = 'approved';
    case rejected = 'rejected';
    case processing = 'processing';
    case paid = 'paid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::submitted => 'Submitted',
            self::under_review => 'Under Review',
            self::approved => 'Approved',
            self::rejected => 'Rejected',
            self::processing => 'Processing',
            self::paid => 'Paid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::submitted => 'gray',
            self::under_review => 'info',
            self::approved => 'success',
            self::rejected => 'danger',
            self::processing => 'warning',
            self::paid => 'success',
        };
    }
}
