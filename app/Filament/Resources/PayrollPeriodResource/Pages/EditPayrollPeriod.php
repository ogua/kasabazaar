<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PayrollPeriodResource;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

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
