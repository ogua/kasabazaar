<x-filament-panels::page>
    @php $summary = $this->getSummary(); @endphp

    <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" wire:model.live="start_date"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" wire:model.live="end_date"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Showing data from {{ \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y') }} to {{ \Carbon\Carbon::parse($summary['end_date'])->format('M d, Y') }}
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Shipments</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $summary['shipment_count'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Revenue (USD)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($summary['total_revenue_usd'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Average Shipment Value</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($summary['average_value_usd'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Monthly Trend</h3>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2">Month</th>
                        <th class="pb-2 text-right">Shipments</th>
                        <th class="pb-2 text-right">Revenue (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_month'] as $row)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ $row['month'] }}</td>
                            <td class="py-2 text-right">{{ $row['shipment_count'] }}</td>
                            <td class="py-2 text-right">${{ number_format($row['revenue_usd'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">By Status</h3>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2">Status</th>
                        <th class="pb-2 text-right">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_status'] as $row)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ $row['status'] }}</td>
                            <td class="py-2 text-right">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
