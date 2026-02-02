<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\VehicleResource;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
}
