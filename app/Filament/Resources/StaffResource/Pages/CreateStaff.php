<?php

namespace App\Filament\Resources\StaffResource\Pages;

use Filament\Actions;
use App\Filament\Resources\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

        protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
