<?php

namespace App\Filament\Resources\StaffRoleResource\Pages;

use App\Filament\Resources\StaffRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffRole extends EditRecord
{
    protected static string $resource = StaffRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
