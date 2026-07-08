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
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Income (USD)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">${{ number_format($summary['total_usd'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Income (GHS)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">₵{{ number_format($summary['total_ghs'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Number of Entries</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $summary['count'] }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Income by Category</h3>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2">Category</th>
                        <th class="pb-2 text-right">Count</th>
                        <th class="pb-2 text-right">Total (USD)</th>
                        <th class="pb-2 text-right">Total (GHS)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_category'] as $row)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ $row['category'] }}</td>
                            <td class="py-2 text-right">{{ $row['count'] }}</td>
                            <td class="py-2 text-right">${{ number_format($row['total_usd'], 2) }}</td>
                            <td class="py-2 text-right">₵{{ number_format($row['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
