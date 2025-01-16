<?php

namespace App\Filament\Client\Resources\ShipmentResource\Pages;

use App\Filament\Client\Resources\ShipmentResource;
use App\Filament\Client\Widgets\ShipmentstatisticsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
          //  Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
          ShipmentstatisticsWidget::class
        ];
    }


    protected function getFooterWidgets(): array
    {
        return [
          //ShipmentstatisticsWidget::class
        ];
    }
}
