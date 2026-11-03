<?php

namespace App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource\Pages;

use App\Filament\Investor\Resources\InvestmentWithdrawalRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestmentWithdrawalRequest extends ViewRecord
{
    protected static string $resource = InvestmentWithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        // A submitted request is reviewed by staff; the investor cannot edit it.
        return [];
    }
}
