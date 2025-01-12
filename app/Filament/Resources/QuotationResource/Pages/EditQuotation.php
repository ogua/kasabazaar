<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function afterSave(): void
    {
        $records = $this->getRecord();

        $amountopay = $records->items->sum('item_cost');
        $total = $amountopay + $records->shipping_cost;

        $records->sub_total = $amountopay;
        $records->total = $total;

        $records->save();
    }

}
