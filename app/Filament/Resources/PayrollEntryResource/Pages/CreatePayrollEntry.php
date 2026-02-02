<?php

namespace App\Filament\Resources\PayrollEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PayrollEntryResource;

class CreatePayrollEntry extends CreateRecord
{
    protected static string $resource = PayrollEntryResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
