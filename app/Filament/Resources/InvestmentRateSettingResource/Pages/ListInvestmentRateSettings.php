<?php

namespace App\Filament\Resources\InvestmentRateSettingResource\Pages;

use App\Filament\Resources\InvestmentRateSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentRateSettings extends ListRecords
{
    protected static string $resource = InvestmentRateSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
