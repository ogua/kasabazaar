<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Filament\Actions\AuditHistoryAction;
use App\Filament\Resources\InvestmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestment extends ViewRecord
{
    protected static string $resource = InvestmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AuditHistoryAction::make(),
            InvestmentResource::postInterestHeaderAction(),
            InvestmentResource::convertCapitalHeaderAction(),
            Actions\EditAction::make(),
        ];
    }
}
