<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Available Balance</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['balance_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending Balance</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['pending_balance_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500 dark:text-gray-400">Lifetime Earnings</div>
            <div class="text-2xl font-semibold">GHS {{ number_format($summary['lifetime_earnings_ghs'] ?? 0, 2) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section heading="Recent Orders" class="mt-4">
        @if (empty($recentOrders))
            <p class="text-sm text-gray-500 dark:text-gray-400">No orders yet.</p>
        @else
            <table class="fi-ta-table w-full text-start">
                <thead>
                    <tr>
                        <th class="p-2 text-start text-xs font-medium text-gray-500">Order</th>
                        <th class="p-2 text-start text-xs font-medium text-gray-500">Status</th>
                        <th class="p-2 text-start text-xs font-medium text-gray-500">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr class="border-t border-gray-100 dark:border-white/5">
                            <td class="p-2">
                                <a href="{{ \App\Filament\Vendor\Pages\Orders\ShowOrder::getUrl(['order' => $order['id']]) }}" class="fi-link text-primary-600">
                                    {{ $order['order_number'] }}
                                </a>
                            </td>
                            <td class="p-2">{{ ucfirst($order['status']) }}</td>
                            <td class="p-2">GHS {{ number_format($order['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
