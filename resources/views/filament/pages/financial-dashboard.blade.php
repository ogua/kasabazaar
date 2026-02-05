<x-filament-panels::page>
    {{-- Date Range Filter --}}
    <div class="mb-6">
        <form wire:submit.prevent="updateDashboard">
            {{ $this->form }}
        </form>
    </div>

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
