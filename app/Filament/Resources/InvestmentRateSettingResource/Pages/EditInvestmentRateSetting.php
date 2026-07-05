<?php

namespace App\Filament\Resources\InvestmentRateSettingResource\Pages;

use App\Filament\Resources\InvestmentRateSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestmentRateSetting extends EditRecord
{
    protected static string $resource = InvestmentRateSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
