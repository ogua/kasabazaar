<?php
namespace App\Filament\Resources\CashbookDirectorAccountResource\Pages;
use App\Filament\Resources\CashbookDirectorAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCashbookDirectorAccount extends EditRecord
{
    protected static string $resource = CashbookDirectorAccountResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
