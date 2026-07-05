<?php

namespace App\Filament\Resources\InvestmentWithdrawalRequestResource\Pages;

use App\Filament\Resources\InvestmentWithdrawalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestmentWithdrawalRequest extends ViewRecord
{
    protected static string $resource = InvestmentWithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
