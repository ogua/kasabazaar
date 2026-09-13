<x-filament-panels::page>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <x-filament::button tag="a" href="{{ $this->getBarcodeLabelUrl() }}" target="_blank" icon="heroicon-o-tag">
            Print Barcode Label
        </x-filament::button>

        <x-filament::button color="gray" tag="a" href="{{ $this->getLabelUrl() }}" target="_blank" icon="heroicon-o-printer">
            Open / Print Tracking QR
        </x-filament::button>

        <x-filament::button color="gray" tag="a" href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}">
            Done — Back to Shipments
        </x-filament::button>
    </div>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <iframe src="{{ $this->getBarcodeLabelUrl() }}" class="h-[80vh] w-full rounded-xl" title="Shipment Barcode Label Preview"></iframe>
    </div>
</x-filament-panels::page>
