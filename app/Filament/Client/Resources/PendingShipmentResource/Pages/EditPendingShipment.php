<?php

namespace App\Filament\Client\Resources\PendingShipmentResource\Pages;

use App\Filament\Client\Resources\PendingShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendingShipment extends EditRecord
{
    protected static string $resource = PendingShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
