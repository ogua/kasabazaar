<?php

namespace App\Filament\Resources\PickupScheduleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PickupScheduleResource;

class ListPickupSchedules extends ListRecords
{
    protected static string $resource = PickupScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
