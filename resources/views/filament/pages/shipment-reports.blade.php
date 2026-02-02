<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Generate Report
            </x-filament::button>
        </div>
    </form>

    @if($this->reportData && $this->reportData->count() > 0)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Report Results
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                @if($this->report_type === 'by_container' || $this->report_type === 'by_year')
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
                                    @if($this->report_type === 'by_container' || $this->report_type === 'by_year')
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

                @if($this->report_type === 'profit_loss')
                    <div class="mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="flex justify-end gap-8">
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Revenue</div>
                                <div class="text-lg font-medium">${{ number_format($this->reportData->sum('revenue'), 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Total Expenses</div>
                                <div class="text-lg font-medium">${{ number_format($this->reportData->sum('expenses'), 2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Net Profit</div>
                                <div class="text-lg font-medium {{ $this->reportData->sum('profit') >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($this->reportData->sum('profit'), 2) }}
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
