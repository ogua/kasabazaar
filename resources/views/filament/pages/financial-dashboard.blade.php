<x-filament-panels::page>
    {{-- Financial Overview Stats --}}
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
    />

    {{-- Charts --}}
    <div class="mt-6">
        <x-filament-widgets::widgets
            :widgets="$this->getFooterWidgets()"
            :columns="2"
        />
    </div>

    {{-- Unpaid Shipments Table --}}
    <div class="mt-6">
        @livewire(\App\Filament\Widgets\UnpaidShipmentsWidget::class)
    </div>
</x-filament-panels::page>
