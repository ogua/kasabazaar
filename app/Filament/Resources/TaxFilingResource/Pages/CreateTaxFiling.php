<?php

namespace App\Filament\Resources\TaxFilingResource\Pages;

use App\Filament\Resources\TaxFilingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxFiling extends CreateRecord
{
    protected static string $resource = TaxFilingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        return $data;
    }
}
