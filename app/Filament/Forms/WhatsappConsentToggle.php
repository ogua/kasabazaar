<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;

/**
 * A toggle bound to a model's whatsapp_opt_in_at timestamp: on keeps the
 * original consent time (or records now), off clears it.
 */
class WhatsappConsentToggle
{
    public static function make(string $label, string $helperText): Toggle
    {
        return Toggle::make('whatsapp_opt_in_at')
            ->label($label)
            ->helperText($helperText)
            ->formatStateUsing(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn (bool $state, ?Model $record) => $state ? ($record?->whatsapp_opt_in_at ?? now()) : null);
    }
}
