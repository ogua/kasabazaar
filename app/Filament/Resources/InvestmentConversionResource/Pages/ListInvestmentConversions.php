<?php

namespace App\Filament\Resources\InvestmentConversionResource\Pages;

use App\Filament\Resources\InvestmentConversionResource;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentConversions extends ListRecords
{
    protected static string $resource = InvestmentConversionResource::class;

    /**
     * No CreateAction: a conversion is always raised against specific tranches, so it
     * is started from the "Convert Capital" action on an investment (or by the
     * investor in their portal), never from a blank form here.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
