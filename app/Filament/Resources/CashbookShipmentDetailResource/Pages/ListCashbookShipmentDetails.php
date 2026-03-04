<?php
namespace App\Filament\Resources\CashbookShipmentDetailResource\Pages;
use App\Filament\Resources\CashbookShipmentDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookShipmentDetails extends ListRecords
{
    protected static string $resource = CashbookShipmentDetailResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
