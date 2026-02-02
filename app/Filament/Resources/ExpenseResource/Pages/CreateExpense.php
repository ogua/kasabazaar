<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ExpenseResource;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Expense created successfully';
    }
}
