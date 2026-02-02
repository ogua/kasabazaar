<?php

namespace App\Filament\Resources\PayrollEntryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PayrollEntryResource;

class EditPayrollEntry extends EditRecord
{
    protected static string $resource = PayrollEntryResource::class;

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
