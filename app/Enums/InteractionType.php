<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InteractionType: string implements HasLabel, HasColor
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Whatsapp = 'whatsapp';
    case Visit = 'visit';
    case Complaint = 'complaint';
    case Inquiry = 'inquiry';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Call => 'Phone Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Whatsapp => 'WhatsApp',
            self::Visit => 'Site Visit',
            self::Complaint => 'Complaint',
            self::Inquiry => 'Inquiry',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Call => 'info',
            self::Email => 'primary',
            self::Meeting => 'success',
            self::Whatsapp => 'success',
            self::Visit => 'warning',
            self::Complaint => 'danger',
            self::Inquiry => 'gray',
        };
    }
}
