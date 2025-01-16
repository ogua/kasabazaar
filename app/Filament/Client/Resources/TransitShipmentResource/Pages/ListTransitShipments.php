<?php

namespace App\Filament\Client\Resources\TransitShipmentResource\Pages;

use App\Filament\Client\Resources\TransitShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransitShipments extends ListRecords
{
    protected static string $resource = TransitShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
