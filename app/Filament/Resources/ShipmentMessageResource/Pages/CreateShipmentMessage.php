<?php

namespace App\Filament\Resources\ShipmentMessageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ShipmentMessageResource;
use App\Service\ShipmentMessageService;
use Filament\Facades\Filament;

class CreateShipmentMessage extends CreateRecord
{
    protected static string $resource = ShipmentMessageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['branch_id'] = Filament::getTenant()?->id;
        $data['sent_by'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Process and send the message
        ShipmentMessageService::processMessage($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
