<?php

namespace App\Filament\Client\Resources\DeliveredShipmentResource\Pages;

use App\Filament\Client\Resources\DeliveredShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveredShipment extends EditRecord
{
    protected static string $resource = DeliveredShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
