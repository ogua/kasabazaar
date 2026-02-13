<?php

namespace App\Filament\Resources\PickupScheduleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PickupScheduleResource;

class EditPickupSchedule extends EditRecord
{
    protected static string $resource = PickupScheduleResource::class;

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
