<?php

namespace App\Filament\Resources\ManualReceiptResource\Pages;

use App\Filament\Resources\ManualReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManualReceipt extends CreateRecord
{
    protected static string $resource = ManualReceiptResource::class;

    protected static ?string $title = 'Generate receipt';

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Generate receipt');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Receipt '.$this->getRecord()->receipt_number.' generated — print it or send it to the client.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
