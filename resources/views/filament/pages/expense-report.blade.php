<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $expenses = $data['expenses'];
        $summary = $data['summary'];
    @endphp

    {{-- Date Range Filter --}}
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">This Month (GHS)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ₵{{ number_format($summary['this_month_ghs'], 2) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                ${{ number_format($summary['this_month_usd'], 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Last Month (GHS)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ₵{{ number_format($summary['last_month_ghs'], 2) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                ${{ number_format($summary['last_month_usd'], 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Growth</div>
            <div class="text-2xl font-bold {{ $summary['growth_percent'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format(abs($summary['growth_percent']), 1) }}%
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $summary['growth_percent'] >= 0 ? '↑ Increase' : '↓ Decrease' }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Expenses</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $summary['total_count'] }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">This month</div>
        </div>
    </div>

    {{-- Expenses by Category --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Expenses by Category (This Month)</h3>
        </div>
        <div class="p-4">
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
                    @foreach($summary['by_category'] as $category => $data)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ $category }}</td>
                            <td class="py-2 text-right">{{ $data['count'] }}</td>
                            <td class="py-2 text-right">${{ number_format($data['total_usd'], 2) }}</td>
                            <td class="py-2 text-right">₵{{ number_format($data['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Expenses Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Expenses (Last 100)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Shipment</th>
                        <th class="px-4 py-3">Stage</th>
                        <th class="px-4 py-3">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($expenses as $expense)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $expense['reference'] }}</td>
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($expense['date'])->format('M d, Y') }}</td>
                            <td class="px-4 py-3">{{ $expense['category'] }}</td>
                            <td class="px-4 py-3 max-w-xs truncate">{{ $expense['description'] ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold">${{ number_format($expense['amount_usd'], 2) }}</div>
                                <div class="text-xs text-gray-500">₵{{ number_format($expense['amount_ghs'], 2) }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $expense['shipment_ref'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $expense['expense_stage'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $expense['recorded_by'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
