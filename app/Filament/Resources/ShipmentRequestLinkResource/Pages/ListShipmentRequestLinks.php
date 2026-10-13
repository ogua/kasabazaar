<?php

namespace App\Filament\Resources\ShipmentRequestLinkResource\Pages;

use App\Filament\Resources\ShipmentRequestLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListShipmentRequestLinks extends ListRecords
{
    protected static string $resource = ShipmentRequestLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ShipmentRequestLinkResource::createLinkAction(),
        ];
    }
}
