<?php

namespace App\Filament\Resources\ShipmentRequestResource\Pages;

use App\Filament\Resources\ShipmentRequestLinkResource;
use App\Filament\Resources\ShipmentRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListShipmentRequests extends ListRecords
{
    protected static string $resource = ShipmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ShipmentRequestLinkResource::createLinkAction(),

            Action::make('pickupLinks')
                ->label('Manage Links')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->url(ShipmentRequestLinkResource::getUrl('index'))
                ->visible(fn (): bool => ShipmentRequestLinkResource::canViewAny()),
        ];
    }
}
