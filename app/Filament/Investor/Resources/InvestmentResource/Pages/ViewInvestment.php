<?php

namespace App\Filament\Investor\Resources\InvestmentResource\Pages;

use App\Filament\Investor\Resources\InvestmentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestment extends ViewRecord
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        // Investors cannot edit a tranche (the resource has no edit page).
        return [];
    }
}
