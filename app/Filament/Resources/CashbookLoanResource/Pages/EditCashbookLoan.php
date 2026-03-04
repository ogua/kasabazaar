<?php
namespace App\Filament\Resources\CashbookLoanResource\Pages;
use App\Filament\Resources\CashbookLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCashbookLoan extends EditRecord
{
    protected static string $resource = CashbookLoanResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
