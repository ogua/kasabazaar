<?php

namespace App\Filament\Resources\CashPositionResource\Pages;

use App\Filament\Resources\CashPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashPositions extends ListRecords
{
    protected static string $resource = CashPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Record Position')];
    }
}
