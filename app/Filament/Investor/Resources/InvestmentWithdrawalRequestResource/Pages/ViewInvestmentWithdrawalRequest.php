<?php

namespace App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource\Pages;

use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource;
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
