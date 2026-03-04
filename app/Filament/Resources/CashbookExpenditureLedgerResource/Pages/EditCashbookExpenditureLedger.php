<?php
namespace App\Filament\Resources\CashbookExpenditureLedgerResource\Pages;
use App\Filament\Resources\CashbookExpenditureLedgerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCashbookExpenditureLedger extends EditRecord
{
    protected static string $resource = CashbookExpenditureLedgerResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
