<?php

namespace App\Filament\Client\Resources\PendingShipmentResource\Pages;

use App\Filament\Client\Resources\PendingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendingShipments extends ListRecords
{
    protected static string $resource = PendingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
