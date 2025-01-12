<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorderd_by'] = auth()->user()->id;
        return $data;
    }


    protected function afterCreate(): void
    {
        $records = $this->getRecord();

        $amountopay = $records->items->sum('item_cost');
        $total = $amountopay + $records->shipping_cost;

        $records->sub_total = $amountopay;
        $records->total = $total;

        $records->save();
    }
}
