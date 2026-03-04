<?php
namespace App\Filament\Resources\CashbookLoanResource\Pages;
use App\Filament\Resources\CashbookLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookLoans extends ListRecords
{
    protected static string $resource = CashbookLoanResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
