<?php

namespace App\Filament\Resources\ManualReceiptResource\Pages;

use App\Filament\Resources\ManualReceiptResource;
use App\Models\ManualReceipt;
use App\Services\ManualReceiptService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewManualReceipt extends ViewRecord
{
    protected static string $resource = ManualReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print receipt')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (ManualReceipt $record): string => app(ManualReceiptService::class)->viewUrl($record), shouldOpenInNewTab: true),

            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (ManualReceipt $record): string => app(ManualReceiptService::class)->downloadUrl($record)),

            ManualReceiptResource::configureSendAction(Actions\Action::make('send')),

            Actions\EditAction::make(),
        ];
    }
}
