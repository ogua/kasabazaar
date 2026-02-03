<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $payrolls = $data['payrolls'];
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
            <div class="text-sm text-gray-500 dark:text-gray-400">This Month</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ₵{{ number_format($summary['this_month'], 2) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Total payroll
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Last Month</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ₵{{ number_format($summary['last_month'], 2) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Total payroll
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
            <div class="text-sm text-gray-500 dark:text-gray-400">Employees Paid</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $summary['total_employees'] }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">This month</div>
        </div>
    </div>

    {{-- Additional Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Average Salary</div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                ₵{{ number_format($summary['avg_salary'], 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Deductions</div>
            <div class="text-xl font-bold text-red-600">
                ₵{{ number_format($summary['total_deductions'], 2) }}
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Bonuses</div>
            <div class="text-xl font-bold text-green-600">
                ₵{{ number_format($summary['total_bonuses'], 2) }}
            </div>
        </div>
    </div>

    {{-- Payroll by Status --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Payroll by Status (This Month)</h3>
        </div>
        <div class="p-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="pb-2">Status</th>
                        <th class="pb-2 text-right">Count</th>
                        <th class="pb-2 text-right">Total</th>
                        <th class="pb-2 text-right">Average</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_status'] as $status => $data)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2 font-medium">{{ ucfirst($status) }}</td>
                            <td class="py-2 text-right">{{ $data['count'] }}</td>
                            <td class="py-2 text-right">₵{{ number_format($data['total'], 2) }}</td>
                            <td class="py-2 text-right">₵{{ number_format($data['avg'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Payroll Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Payroll Entries (Last 100)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900 text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Pay Date</th>
                        <th class="px-4 py-3 text-right">Gross Salary</th>
                        <th class="px-4 py-3 text-right">Deductions</th>
                        <th class="px-4 py-3 text-right">Bonuses</th>
                        <th class="px-4 py-3 text-right">Net Salary</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($payrolls as $payroll)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium">{{ $payroll['employee'] }}</td>
                            <td class="px-4 py-3 text-xs">{{ $payroll['period'] }}</td>
                            <td class="px-4 py-3">
                                {{ $payroll['pay_date'] ? \Carbon\Carbon::parse($payroll['pay_date'])->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-right">₵{{ number_format($payroll['gross_salary'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">₵{{ number_format($payroll['deductions'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-green-600">₵{{ number_format($payroll['bonuses'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold">₵{{ number_format($payroll['net_salary'], 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs
                                    @if($payroll['status'] === 'Paid')
                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($payroll['status'] === 'Pending')
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else
                                        bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $payroll['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
