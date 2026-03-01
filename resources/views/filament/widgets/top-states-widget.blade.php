<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Top 10 States by Shipment Volume
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">State/Region</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Total Shipments</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Total Revenue (USD)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Total Revenue (GHS)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Avg. Value (USD)</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Payments Received</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Payment Rate</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Market Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->getStatesData() as $index => $state)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                    {{ $index === 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                       ($index === 1 ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' :
                                       ($index === 2 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' :
                                       'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200')) }}
                                    font-bold text-lg">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                {{ $state['state_name'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ number_format($state['shipment_count']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                ${{ number_format($state['total_revenue_usd'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                ₵{{ number_format($state['total_revenue_ghs'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                ${{ number_format($state['avg_shipment_value_usd'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                ₵{{ number_format($state['payments_received'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($state['payment_rate_class'] === 'success')
                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($state['payment_rate_class'] === 'warning')
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else
                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @endif">
                                    {{ number_format($state['payment_rate'], 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ number_format($state['market_share'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No state data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-4">
                <div>
                    <strong>Payment Rate:</strong>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                    <span>Excellent (≥80%)</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span>Good (50-79%)</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
                    <span>Poor (&lt;50%)</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
