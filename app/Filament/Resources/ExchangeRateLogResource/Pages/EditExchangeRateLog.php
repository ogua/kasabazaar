<?php

namespace App\Filament\Resources\ExchangeRateLogResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ExchangeRateLogResource;

class EditExchangeRateLog extends EditRecord
{
    protected static string $resource = ExchangeRateLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
