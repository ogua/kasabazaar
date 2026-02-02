<?php

namespace App\Filament\Resources\StaffResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\StaffResource;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

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
