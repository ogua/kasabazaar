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

            @if ($this->reportData && $this->reportData->count() > 0)
                <div class="flex gap-2">
                    <x-filament::button color="success" icon="heroicon-o-printer" onclick="window.print()">
                        Print
                    </x-filament::button>

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
                </div>
            @endif
        </div>
    </form>

    @if ($this->reportData && $this->reportData->count() > 0)
        <div class="mt-6" id="client-report-printable">
            <x-filament::section>
                <x-slot name="heading">Report Results</x-slot>

                @if ($this->report_type === 'client_summary' || $this->report_type === 'top_clients')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-4 py-2 text-left">Client</th>
                                    <th class="px-4 py-2 text-right">Shipments</th>
                                    <th class="px-4 py-2 text-right">Revenue (USD)</th>
                                    <th class="px-4 py-2 text-right">Paid (USD)</th>
                                    <th class="px-4 py-2 text-right">Balance (USD)</th>
                                    <th class="px-4 py-2 text-left">Payment Status Mix</th>
                                    <th class="px-4 py-2 text-left">Shipping Status Mix</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->reportData as $row)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-4 py-2">
                                            <div class="font-medium">{{ $row['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $row['phone'] }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-right">{{ $row['shipment_count'] }}</td>
                                        <td class="px-4 py-2 text-right">${{ number_format($row['total_usd'], 2) }}</td>
                                        <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">${{ number_format($row['paid_usd'], 2) }}</td>
                                        <td class="px-4 py-2 text-right {{ $row['balance_usd'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                            ${{ number_format($row['balance_usd'], 2) }}
                                        </td>
                                        <td class="px-4 py-2 text-xs">
                                            @foreach ($row['payment_status_counts'] as $status => $count)
                                                @if ($count > 0)
                                                    <span class="inline-block mr-1">{{ ucfirst($status) }}: {{ $count }}</span>
                                                @endif
                                            @endforeach
                                        </td>
                                        <td class="px-4 py-2 text-xs">
                                            @foreach ($row['shipping_status_counts'] as $status => $count)
                                                @if ($count > 0)
                                                    <span class="inline-block mr-1">{{ ucfirst($status) }}: {{ $count }}</span>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($this->report_type === 'new_clients')
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-4 py-2 text-left">Client</th>
                                    <th class="px-4 py-2 text-left">Phone</th>
                                    <th class="px-4 py-2 text-left">Email</th>
                                    <th class="px-4 py-2 text-left">Registered</th>
                                    <th class="px-4 py-2 text-right">Shipments Since</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->reportData as $client)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-4 py-2 font-medium">{{ $client->name }}</td>
                                        <td class="px-4 py-2">{{ $client->phone }}</td>
                                        <td class="px-4 py-2">{{ $client->email }}</td>
                                        <td class="px-4 py-2">{{ $client->created_at?->format('M d, Y') }}</td>
                                        <td class="px-4 py-2 text-right">{{ $client->shipments_count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($this->report_type === 'status_breakdown')
                    @php $stats = $this->reportData->first(); @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Clients</div>
                            <div class="text-2xl font-bold">{{ $stats['total_clients'] }}</div>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Shipments</div>
                            <div class="text-2xl font-bold">{{ $stats['total_shipments'] }}</div>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Total Revenue</div>
                            <div class="text-2xl font-bold">${{ number_format($stats['total_revenue_usd'], 2) }}</div>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Outstanding</div>
                            <div class="text-2xl font-bold {{ $stats['total_outstanding_usd'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                ${{ number_format($stats['total_outstanding_usd'], 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-2">Shipping Status Breakdown</h3>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="px-3 py-1.5 text-left">Status</th>
                                        <th class="px-3 py-1.5 text-right">Count</th>
                                        <th class="px-3 py-1.5 text-right">%</th>
                                        <th class="px-3 py-1.5 text-right">Value (USD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stats['shipping_status_breakdown'] as $row)
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-3 py-1.5">{{ ucfirst($row['label']) }}</td>
                                            <td class="px-3 py-1.5 text-right">{{ $row['count'] }}</td>
                                            <td class="px-3 py-1.5 text-right">{{ $row['percentage'] }}%</td>
                                            <td class="px-3 py-1.5 text-right">${{ number_format($row['total_usd'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h3 class="font-semibold mb-2">Payment Status Breakdown</h3>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b dark:border-gray-700">
                                        <th class="px-3 py-1.5 text-left">Status</th>
                                        <th class="px-3 py-1.5 text-right">Count</th>
                                        <th class="px-3 py-1.5 text-right">%</th>
                                        <th class="px-3 py-1.5 text-right">Value (USD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stats['payment_status_breakdown'] as $row)
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-3 py-1.5">{{ $row['label'] }}</td>
                                            <td class="px-3 py-1.5 text-right">{{ $row['count'] }}</td>
                                            <td class="px-3 py-1.5 text-right">{{ $row['percentage'] }}%</td>
                                            <td class="px-3 py-1.5 text-right">${{ number_format($row['total_usd'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>
    @elseif ($this->reportData !== null)
        <div class="mt-6">
            <x-filament::section>
                <p class="text-gray-500 text-center py-4">No data found for the selected criteria.</p>
            </x-filament::section>
        </div>
    @endif

    <style>
        @media print {
            body * { visibility: hidden; }
            #client-report-printable, #client-report-printable * { visibility: visible; }
            #client-report-printable { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</x-filament-panels::page>
