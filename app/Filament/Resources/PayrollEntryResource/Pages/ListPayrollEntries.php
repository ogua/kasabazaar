<?php

namespace App\Filament\Resources\PayrollEntryResource\Pages;

use App\Filament\Resources\PayrollEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrollEntries extends ListRecords
{
    protected static string $resource = PayrollEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
