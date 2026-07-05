<?php

namespace App\Filament\Resources\InvestmentAnnualStatementResource\Pages;

use App\Filament\Resources\InvestmentAnnualStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestmentAnnualStatement extends EditRecord
{
    protected static string $resource = InvestmentAnnualStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
