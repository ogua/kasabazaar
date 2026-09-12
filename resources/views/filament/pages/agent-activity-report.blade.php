<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="generateReport">
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">Generating...</span>
            </x-filament::button>

            @if(($this->clearances && $this->clearances->count() > 0) || ($this->deliveries && $this->deliveries->count() > 0))
                <x-filament::button color="success" icon="heroicon-o-table-cells" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel">
                    Export Excel
                </x-filament::button>
            @endif
        </div>
    </form>

    @if ($this->clearances !== null || $this->deliveries !== null)
        <div class="mt-6 space-y-6">
            <x-filament::section>
                <x-slot name="heading">Container Clearances</x-slot>

                @if ($this->clearances && $this->clearances->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 pr-4">Agent</th>
                                    <th class="py-2 pr-4">Container</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Recorded By</th>
                                    <th class="py-2 pr-4">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->clearances as $clearance)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-4">{{ $clearance->clearingAgent?->name }}</td>
                                        <td class="py-2 pr-4">CON{{ $clearance->container_number }}</td>
                                        <td class="py-2 pr-4">
                                            <x-filament::badge :color="$clearance->is_cleared ? 'success' : 'warning'">
                                                {{ $clearance->is_cleared ? 'Cleared' : 'Not Cleared' }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="py-2 pr-4">{{ $clearance->recordedBy?->name }}</td>
                                        <td class="py-2 pr-4">{{ $clearance->cleared_at?->format('g:ia') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No clearances recorded for this date.</p>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Shipment Deliveries</x-slot>

                @if ($this->deliveries && $this->deliveries->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2 pr-4">Agent</th>
                                    <th class="py-2 pr-4">Shipment</th>
                                    <th class="py-2 pr-4">Notes</th>
                                    <th class="py-2 pr-4">Recorded By</th>
                                    <th class="py-2 pr-4">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->deliveries as $delivery)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-4">{{ $delivery->clearingAgent?->name }}</td>
                                        <td class="py-2 pr-4">{{ $delivery->shipment?->shipping_reference }}</td>
                                        <td class="py-2 pr-4">{{ $delivery->delivery_notes }}</td>
                                        <td class="py-2 pr-4">{{ $delivery->recordedBy?->name }}</td>
                                        <td class="py-2 pr-4">{{ $delivery->delivered_at?->format('g:ia') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No deliveries recorded for this date.</p>
                @endif
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
