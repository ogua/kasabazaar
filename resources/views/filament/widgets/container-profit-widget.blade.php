<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Last 10 Containers - Profit/Loss Analysis
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Container</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Shipments</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Revenue</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Payments Received</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Expenses</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Profit/Loss</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Margin %</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->getContainerData() as $container)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-gray-100">
                                {{ $container['container_ref'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $container['shipment_count'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    ₵{{ number_format($container['total_revenue_ghs'], 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ${{ number_format($container['total_revenue_usd'], 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    ₵{{ number_format($container['payments_received_ghs'], 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ${{ number_format($container['payments_received_usd'], 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    ₵{{ number_format($container['expenses_ghs'], 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ${{ number_format($container['expenses_usd'], 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                        @if($container['profit_loss_ghs'] >= 0)
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else
                                            bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                        ₵{{ number_format($container['profit_loss_ghs'], 2) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ${{ number_format($container['profit_loss_usd'], 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($container['margin_class'] === 'success')
                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($container['margin_class'] === 'warning')
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else
                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @endif">
                                    {{ number_format($container['margin_percent'], 1) }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($container['container_date'])->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No container data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                    <span>Excellent (≥20% margin)</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span>Good (0-20% margin)</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
                    <span>Loss (&lt;0% margin)</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
