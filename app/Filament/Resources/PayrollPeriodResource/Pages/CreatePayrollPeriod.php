<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PayrollPeriodResource;

class CreatePayrollPeriod extends CreateRecord
{
    protected static string $resource = PayrollPeriodResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
