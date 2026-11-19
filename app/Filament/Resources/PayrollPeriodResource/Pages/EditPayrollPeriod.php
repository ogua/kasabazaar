<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Actions\AuditHistoryAction;
use App\Filament\Resources\PayrollPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AuditHistoryAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
