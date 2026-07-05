<?php

namespace App\Filament\Resources\InvestmentWithdrawalRequestResource\Pages;

use App\Filament\Resources\InvestmentWithdrawalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentWithdrawalRequests extends ListRecords
{
    protected static string $resource = InvestmentWithdrawalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
