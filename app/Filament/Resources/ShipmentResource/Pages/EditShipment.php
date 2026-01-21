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
        // Parse and sync shipping_reference fields if reference was edited
        if (!empty($data['shipping_reference'])) {
            $parsed = \App\Models\Shipment::parseShippingReference($data['shipping_reference']);
            if ($parsed) {
                $data['container_number'] = $parsed['container_number'];
                $data['client_sequence'] = $parsed['container_seq_year'];
                $data['total_shipment_sequence'] = $parsed['client_seq'];
            }
        }

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
        $shipment = $this->getRecord();

        $amountopay = $shipment->total;
        $paid = $shipment->payments->sum('amount');

        $shipment->total = $amountopay;
        $shipment->paid = $paid;

        // Update payment status based on payments
        if ($paid >= $amountopay && $amountopay > 0) {
            $shipment->payment_status = 'paid';
        } elseif ($paid > 0) {
            $shipment->payment_status = 'partial';
        } else {
            $shipment->payment_status = 'pending';
        }

        $shipment->save();

        // Determine invoice status
        $invoiceStatus = 'pending';
        if ($paid >= $amountopay && $amountopay > 0) {
            $invoiceStatus = 'paid';
        } elseif ($paid > 0) {
            $invoiceStatus = 'partial';
        }

        Invoice::where('shipment_id', $shipment->id)->update([
            'total_amount' => $shipment->total,
            'status' => $invoiceStatus,
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
                \Filament\Notifications\Actions\Action::make('print_label')
                    ->label('Print Shipping Label')
                    ->url(route('shipping-label', $shipment->id))
                    ->openUrlInNewTab()
                    ->color('warning'),
            ])
            ->persistent()
            ->send();
}
    
}
