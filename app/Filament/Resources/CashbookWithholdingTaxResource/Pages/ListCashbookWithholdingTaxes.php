<?php
namespace App\Filament\Resources\CashbookWithholdingTaxResource\Pages;
use App\Filament\Resources\CashbookWithholdingTaxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookWithholdingTaxes extends ListRecords
{
    protected static string $resource = CashbookWithholdingTaxResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
