<?php

namespace App\Filament\Client\Resources\DeliveredShipmentResource\Pages;

use App\Filament\Client\Resources\DeliveredShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveredShipments extends ListRecords
{
    protected static string $resource = DeliveredShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
