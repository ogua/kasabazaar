<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncome extends CreateRecord
{
    protected static string $resource = IncomeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        return $data;
    }

     protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
