<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\IncomeResource;

class EditIncome extends EditRecord
{
    protected static string $resource = IncomeResource::class;

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
