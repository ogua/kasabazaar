<x-filament-panels::page>
    <x-filament::section>
        <p>Status: <strong>{{ ucfirst($order['status']) }}</strong> — Customer: {{ $order['user']['name'] ?? '—' }}</p>
    </x-filament::section>

    <x-filament::section heading="Items" class="mt-4">
        <table class="fi-ta-table w-full text-start">
            <thead>
                <tr>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Product</th>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Qty</th>
                    <th class="p-2 text-start text-xs font-medium text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order['items'] ?? [] as $item)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <td class="p-2">{{ $item['product_name'] }}</td>
                        <td class="p-2">{{ $item['quantity'] }}</td>
                        <td class="p-2">GHS {{ number_format($item['total_ghs'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="mt-4"><strong>Total: GHS {{ number_format($order['total_ghs'], 2) }}</strong></p>
    </x-filament::section>

    @if (!empty($order['delivery_detail']))
        <x-filament::section heading="Delivery Address" class="mt-4">
            <p>
                {{ $order['delivery_detail']['full_name'] }}, {{ $order['delivery_detail']['phone'] }}<br>
                {{ $order['delivery_detail']['street'] }}, {{ $order['delivery_detail']['city'] }}, {{ $order['delivery_detail']['region'] }}
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
