<?php
namespace App\Filament\Resources\CashbookWithholdingTaxResource\Pages;
use App\Filament\Resources\CashbookWithholdingTaxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCashbookWithholdingTax extends EditRecord
{
    protected static string $resource = CashbookWithholdingTaxResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
