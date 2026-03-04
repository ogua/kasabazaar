<?php
namespace App\Filament\Resources\CashbookDirectorAccountResource\Pages;
use App\Filament\Resources\CashbookDirectorAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCashbookDirectorAccounts extends ListRecords
{
    protected static string $resource = CashbookDirectorAccountResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
