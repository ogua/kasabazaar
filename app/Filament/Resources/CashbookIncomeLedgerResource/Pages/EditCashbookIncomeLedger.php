<?php
namespace App\Filament\Resources\CashbookIncomeLedgerResource\Pages;
use App\Filament\Resources\CashbookIncomeLedgerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCashbookIncomeLedger extends EditRecord
{
    protected static string $resource = CashbookIncomeLedgerResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
