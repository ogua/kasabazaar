<?php
namespace App\Filament\Resources\CashbookIncomeLedgerResource\Pages;
use App\Filament\Resources\CashbookIncomeLedgerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookIncomeLedgers extends ListRecords
{
    protected static string $resource = CashbookIncomeLedgerResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
