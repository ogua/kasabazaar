<?php

namespace App\Filament\Resources\EcommerceShipmentResource\Pages;

use App\Filament\Resources\EcommerceShipmentResource;
use Filament\Resources\Pages\ListRecords;

class ListEcommerceShipments extends ListRecords
{
    protected static string $resource = EcommerceShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
