<?php

namespace App\Filament\Resources\EcommerceShipmentResource\Pages;

use App\Enums\EcommerceOrderShipmentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Filament\Resources\EcommerceShipmentResource;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceShipmentTrackingLog;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewEcommerceShipment extends ViewRecord
{
    protected static string $resource = EcommerceShipmentResource::class;

    protected function resolveRecord(int|string $key): EcommerceOrderShipment
    {
        return EcommerceOrderShipment::with(['order.user', 'trackingLogs'])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('update_shipment')
                ->label('Update Shipment')
                ->icon('heroicon-m-pencil')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('tracking_number')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('courier')
                        ->nullable()
                        ->maxLength(100),
                    Forms\Components\DatePicker::make('estimated_delivery')
                        ->nullable(),
                    Forms\Components\Select::make('status')
                        ->options(EcommerceOrderShipmentStatus::class)
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('notes')
                        ->nullable()
                        ->rows(2),
                ])
                ->fillForm(fn (): array => [
                    'tracking_number' => $this->record->tracking_number,
                    'courier' => $this->record->courier,
                    'estimated_delivery' => $this->record->estimated_delivery?->toDateString(),
                    'status' => $this->record->status->value,
                    'notes' => $this->record->notes,
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'tracking_number' => $data['tracking_number'],
                        'courier' => $data['courier'] ?? null,
                        'estimated_delivery' => $data['estimated_delivery'] ?? null,
                        'status' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                    ]);
                })
                ->successNotificationTitle('Shipment updated'),

            Actions\Action::make('add_tracking')
                ->label('Add Tracking Point')
                ->icon('heroicon-m-map-pin')
                ->color('info')
                ->form([
                    Forms\Components\TextInput::make('latitude')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('longitude')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('status')
                        ->nullable()
                        ->maxLength(100),
                    Forms\Components\DateTimePicker::make('recorded_at')
                        ->default(now())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    EcommerceShipmentTrackingLog::create([
                        'order_shipment_id' => $this->record->id,
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'status' => $data['status'] ?? null,
                        'recorded_at' => $data['recorded_at'],
                    ]);

                    if (
                        ! empty($data['status']) &&
                        str_contains(strtolower((string) $data['status']), 'transit') &&
                        $this->record->order->status !== EcommerceOrderStatus::InTransit
                    ) {
                        $this->record->update(['status' => EcommerceOrderShipmentStatus::InTransit]);
                        $this->record->order->update(['status' => EcommerceOrderStatus::InTransit]);
                        $this->record->order->statusHistory()->create([
                            'status' => EcommerceOrderStatus::InTransit->value,
                            'notes' => 'Order is now in transit.',
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $this->record->refresh();
                })
                ->successNotificationTitle('Tracking point added'),
        ];
    }
}
