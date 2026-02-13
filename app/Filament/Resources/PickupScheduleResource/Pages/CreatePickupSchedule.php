<?php

namespace App\Filament\Resources\PickupScheduleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PickupScheduleResource;

class CreatePickupSchedule extends CreateRecord
{
    protected static string $resource = PickupScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
