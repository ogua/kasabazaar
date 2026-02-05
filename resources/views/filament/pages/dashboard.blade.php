<x-filament-panels::page>
    {{-- Dashboard Filters --}}
    <div class="mb-6">
        <form wire:submit.prevent="updateDashboard">
            {{ $this->form }}
        </form>
    </div>

    {{-- Dashboard Widgets --}}
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
