<?php

namespace App\Filament\Resources\ShipmentMessageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ShipmentMessageResource;

class ListShipmentMessages extends ListRecords
{
    protected static string $resource = ShipmentMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Message'),
        ];
    }
}
