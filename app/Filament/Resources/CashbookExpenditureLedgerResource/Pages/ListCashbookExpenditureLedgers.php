<?php
namespace App\Filament\Resources\CashbookExpenditureLedgerResource\Pages;
use App\Filament\Resources\CashbookExpenditureLedgerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookExpenditureLedgers extends ListRecords
{
    protected static string $resource = CashbookExpenditureLedgerResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
