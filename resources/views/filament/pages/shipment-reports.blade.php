<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="generateReport">
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">
                    <x-filament::loading-indicator class="h-4 w-4 inline-block mr-2" />
                    Generating...
                </span>
            </x-filament::button>

            @if($this->reportData && $this->reportData->count() > 0)
                <div class="flex gap-2">
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-document-arrow-down"
                        wire:click="exportPdf"
                        wire:loading.attr="disabled"
                        wire:target="exportPdf"
                    >
                        <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
                        <span wire:loading wire:target="exportPdf">Exporting...</span>
                    </x-filament::button>

                    <x-filament::button
                        color="success"
                        icon="heroicon-o-table-cells"
                        wire:click="exportExcel"
                        wire:loading.attr="disabled"
                        wire:target="exportExcel"
                    >
                        <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                        <span wire:loading wire:target="exportExcel">Exporting...</span>
                    </x-filament::button>

                    <x-filament::button
                        color="success"
                        icon="heroicon-o-document-text"
                        wire:click="exportWord"
                        wire:loading.attr="disabled"
                        wire:target="exportWord"
                    >
                        <span wire:loading.remove wire:target="exportWord">Export Word</span>
                        <span wire:loading wire:target="exportWord">Exporting...</span>
                    </x-filament::button>
                </div>
            @endif
        </div>
    </form>

    @if($this->reportData && $this->reportData->count() > 0)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Report Results
                </x-slot>

                @if($this->report_type === 'by_container' || $this->report_type === 'client_shipments')
                    {{-- PDF-Style Container Report --}}
                    <div class="space-y-6">
                        @foreach($this->reportData as $shipment)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                                {{-- Client Header --}}
                                <div class="mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-base">
                                                CLIENT: {{ strtoupper($shipment->client?->company_name ?? $shipment->client?->name ?? 'N/A') }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Phone: {{ $shipment->client?->phone ?? 'N/A' }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Reference: <span class="font-medium">{{ $shipment->shipping_reference }}</span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                Date: <span class="font-medium">{{ $shipment->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <x-filament::badge :color="$shipment->status?->getColor() ?? 'gray'">
                                                {{ $shipment->status?->getLabel() ?? 'Unknown' }}
                                            </x-filament::badge>
                                            <div class="text-sm mt-2 font-medium">
                                                Total: ${{ number_format($shipment->total ?? 0, 2) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Paid: ${{ number_format($shipment->paid ?? 0, 2) }}
                                            </div>
                                            @if(($shipment->total - $shipment->paid) > 0)
                                                <div class="text-xs text-red-600 dark:text-red-400">
                                                    Balance: ${{ number_format($shipment->total - $shipment->paid, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Receivers and Items --}}
                                <div class="space-y-4">
                                    @forelse($shipment->receivers as $receiver)
                                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-3">
                                            <div class="mb-2">
                                                <div class="font-medium text-sm">
                                                    RECEIVER: {{ strtoupper($receiver->receiver_name ?: 'SELF') }}
                                                </div>
                                                @if($receiver->receiver_phone)
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                                        Contact: {{ $receiver->receiver_phone }}
                                                    </div>
                                                @endif
                                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                                    Location: {{ strtoupper($receiver->city ?? $receiver->address ?? 'N/A') }}
                                                </div>
                                            </div>

                                            {{-- Items List --}}
                                            @if($receiver->items && $receiver->items->count() > 0)
                                                <div class="mt-2">
                                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ITEMS</div>
                                                    <ul class="list-disc list-inside text-sm space-y-0.5 text-gray-700 dark:text-gray-300">
                                                        @foreach($receiver->items as $item)
                                                            <li>{{ strtoupper($item->product?->name ?? $item->description ?? 'N/A') }}
                                                                @if($item->quantity > 1)
                                                                    <span class="text-gray-500">({{ $item->quantity }})</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="text-sm text-gray-500 italic">No items listed</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-sm text-gray-500 italic">No receivers listed</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Table Format for other reports --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    @if($this->report_type === 'by_year')
                                        <th class="px-4 py-2 text-left">Reference</th>
                                        <th class="px-4 py-2 text-left">Client</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-right">Total (USD)</th>
                                        <th class="px-4 py-2 text-left">Date</th>
                                    @elseif($this->report_type === 'profit_loss')
                                        <th class="px-4 py-2 text-left">Container</th>
                                        <th class="px-4 py-2 text-right">Shipments</th>
                                        <th class="px-4 py-2 text-right">Revenue (USD)</th>
                                        <th class="px-4 py-2 text-right">Expenses (USD)</th>
                                        <th class="px-4 py-2 text-right">Profit (USD)</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->reportData as $row)
                                    <tr class="border-b dark:border-gray-700">
                                        @if($this->report_type === 'by_year')
                                            <td class="px-4 py-2">{{ $row->shipping_reference ?? 'N/A' }}</td>
                                            <td class="px-4 py-2">{{ $row->client?->company_name ?? $row->client?->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2">
                                                <x-filament::badge :color="$row->status?->getColor() ?? 'gray'">
                                                    {{ $row->status?->getLabel() ?? 'Unknown' }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-4 py-2 text-right">${{ number_format($row->total ?? 0, 2) }}</td>
                                            <td class="px-4 py-2">{{ $row->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                        @elseif($this->report_type === 'profit_loss')
                                            <td class="px-4 py-2">{{ $row['container'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-right">{{ $row['shipment_count'] ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right">${{ number_format($row['revenue'] ?? 0, 2) }}</td>
                                            <td class="px-4 py-2 text-right">${{ number_format($row['expenses'] ?? 0, 2) }}</td>
                                            <td class="px-4 py-2 text-right font-medium {{ ($row['profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ${{ number_format($row['profit'] ?? 0, 2) }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($this->report_type === 'profit_loss')
                    <div class="mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="flex justify-end gap-8">
                            @php
                                $totalRevenue = $this->reportData->sum(fn($item) => $item['revenue'] ?? 0);
                                $totalExpenses = $this->reportData->sum(fn($item) => $item['expenses'] ?? 0);
                                $totalProfit = $this->reportData->sum(fn($item) => $item['profit'] ?? 0);
                            @endphp
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Revenue</div>
                                <div class="text-lg font-medium">${{ number_format($totalRevenue, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Expenses</div>
                                <div class="text-lg font-medium">${{ number_format($totalExpenses, 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Net Profit</div>
                                <div class="text-lg font-medium {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($totalProfit, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>
    @elseif($this->reportData !== null)
        <div class="mt-6">
            <x-filament::section>
                <p class="text-gray-500 text-center py-4">No data found for the selected criteria.</p>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
