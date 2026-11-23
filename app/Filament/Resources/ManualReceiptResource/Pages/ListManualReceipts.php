<?php

namespace App\Filament\Resources\ManualReceiptResource\Pages;

use App\Filament\Resources\ManualReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManualReceipts extends ListRecords
{
    protected static string $resource = ManualReceiptResource::class;

    protected ?string $subheading = 'Receipts for payments already received outside the system. These do not affect the accounts.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Generate receipt')
                ->icon('heroicon-o-plus'),
        ];
    }
}
