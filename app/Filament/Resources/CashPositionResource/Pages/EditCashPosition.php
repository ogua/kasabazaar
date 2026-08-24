<?php

namespace App\Filament\Resources\CashPositionResource\Pages;

use App\Filament\Resources\CashPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashPosition extends EditRecord
{
    protected static string $resource = CashPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
