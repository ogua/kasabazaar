<?php

namespace App\Filament\Resources\PickupStationResource\Pages;

use App\Filament\Resources\PickupStationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePickupStations extends ManageRecords
{
    protected static string $resource = PickupStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
