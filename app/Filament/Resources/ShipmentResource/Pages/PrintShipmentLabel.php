<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintShipmentLabel extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static string $view = 'filament.resources.shipment-resource.pages.print-shipment-label';

    protected static ?string $title = 'Print Shipment Label';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->record->loadMissing(['receivers.items.product', 'containerStatus']);
    }

    public function getLabelUrl(): string
    {
        return route('shipment-label-lookup', $this->record->public_view_token);
    }

    public function getBarcodeLabelUrl(): string
    {
        return route('shipping-label', $this->record->id);
    }
}
