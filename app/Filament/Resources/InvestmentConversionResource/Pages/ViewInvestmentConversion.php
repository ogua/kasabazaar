<?php

namespace App\Filament\Resources\InvestmentConversionResource\Pages;

use App\Filament\Resources\InvestmentConversionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestmentConversion extends ViewRecord
{
    protected static string $resource = InvestmentConversionResource::class;

    /**
     * The same approve / reject / execute / reverse actions the table rows carry, so a
     * conversion opened for review can be decided without going back to the list.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return InvestmentConversionResource::reviewActions(Action::class);
    }
}
