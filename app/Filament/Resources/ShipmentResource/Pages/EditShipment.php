<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use Filament\Forms;
use App\Models\City;
use App\Models\State;
use Filament\Actions;
use App\Models\Country;
use App\Models\Invoice;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ShipmentResource;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class EditShipment extends EditRecord
{
    use EditRecord\Concerns\HasWizard;

    protected static string $resource = ShipmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['single_receiver_name']);
        unset($data['single_receiver_phone']);
        unset($data['single_receiver_email']);
        unset($data['single_receiver_id_type']);
        unset($data['single_receiver_id_number']);
        unset($data['single_receiver_country']);
        unset($data['single_receiver_state']);
        unset($data['single_receiver_city']);
        unset($data['single_receiver_address']);
        unset($data['receiver_mode']);

        return $data;
    }

    protected function getSteps(): array
    {
        return CreateShipment::formSteps();
    }

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

       // $amountopay = $records->items->sum('item_cost');
        $amountopay = $records->total;
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

    // Add any additional methods if needed
protected function getSavedNotification(): ?Notification
{
    $shipment = $this->getRecord();
    
    return // Show success notification with invoice options
        Notification::make()
            ->title('Shipment Created!')
            ->body("Tracking: {$shipment->tracking_number}")
            ->success()
            ->actions([
                // \Filament\Notifications\Actions\Action::make('view_invoice')
                //     ->label('View Invoice')
                //     ->url(route('shipping-invoice', $shipment->id))
                //     ->openUrlInNewTab(),
                \Filament\Notifications\Actions\Action::make('download_pdf')
                    ->label('View Invoice')
                    ->url(route('shipping-invoice-pdf', $shipment->id))
                    ->openUrlInNewTab(),
            ])
            ->persistent()
            ->send();
}
    
}
