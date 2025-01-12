<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

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
        $paid = $records->payments->sum('amount');

        $records->total = $amountopay;
        $records->paid = $paid;
        $records->save();

        $left = $amountopay - $paid;

        Invoice::where('shipment_id', $records->id)->update([
            'total_amount' => $records->total,
            'status' => $left < 1 ? 'paid' : 'unpaid',
        ]);
    }
}
