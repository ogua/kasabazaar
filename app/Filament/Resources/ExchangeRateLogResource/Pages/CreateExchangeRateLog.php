<?php

namespace App\Filament\Resources\ExchangeRateLogResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ExchangeRateLogResource;

class CreateExchangeRateLog extends CreateRecord
{
    protected static string $resource = ExchangeRateLogResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
