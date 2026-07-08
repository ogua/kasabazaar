<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestmentWebhookEventStatus: string implements HasColor, HasLabel
{
    case processed = 'processed';
    case ignored = 'ignored';
    case failed = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::processed => 'Processed',
            self::ignored => 'Ignored',
            self::failed => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::processed => 'success',
            self::ignored => 'gray',
            self::failed => 'danger',
        };
    }
}
