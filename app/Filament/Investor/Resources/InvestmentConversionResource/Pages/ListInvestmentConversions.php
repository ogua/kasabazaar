<?php

namespace App\Filament\Investor\Resources\InvestmentConversionResource\Pages;

use App\Filament\Investor\Resources\InvestmentConversionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentConversions extends ListRecords
{
    protected static string $resource = InvestmentConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Request a Conversion'),
        ];
    }
}
