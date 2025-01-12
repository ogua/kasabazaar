<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Invoice;
use App\Models\ShipmentUpdate;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    function generatePinAndSerial()
    {
        // Generate a random alphanumeric 6-digit PIN
        $pin = strtoupper(bin2hex(random_bytes(3))); // Produces a 6-character alphanumeric string

        // Generate a unique 6-digit numeric PIN
        $uniquePin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create the serial number
        $serialNumber = "KBZ" . $uniquePin;

        return [
            'pin' => $pin,
            'tracking_number' => $serialNumber,
        ];
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorderd_by'] = auth()->user()->id;
        $data['tracking_number'] = $this->generatePinAndSerial()['tracking_number'];

        return $data;
    }


    protected function afterCreate(): void
    {
        $records = $this->getRecord();

        $amountopay = $records->items->sum('item_cost');
        $paid = $records->payments->sum('amount');

        $records->total = $amountopay;
        $records->paid = $paid;
        $records->save();

        $left = $amountopay - $paid;

        Invoice::create([
            'shipment_id' => $records->id,
            'total_amount' => $records->total,
            'status' => $left < 1 ? 'paid' : 'unpaid',
        ]);

        ShipmentUpdate::create([
            'shipment_id' => $records->id,
            'location' => $records->branch?->name,
            'status' => $records->status->value,
            'remarks' => $records->status->value,
        ]);
    }
}
