<?php

namespace App\Filament\Resources\CashPositionResource\Pages;

use App\Filament\Resources\CashPositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashPosition extends CreateRecord
{
    protected static string $resource = CashPositionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }
}
