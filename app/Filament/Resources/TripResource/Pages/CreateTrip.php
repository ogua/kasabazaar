<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
