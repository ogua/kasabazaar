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

    <x-filament::section heading="Where this order goes" class="mt-4">
        <p>
            Orders are not delivered to buyers by vendors. When the order is packed, send it to a KASAROSE warehouse.
            We inspect it there, then deliver it to the customer ourselves. Returns also come to our warehouse and are
            checked before they come back to you.
        </p>
        @if (! empty($order['warehouse']))
            <p class="mt-2"><strong>Warehouse:</strong> {{ $order['warehouse']['name'] }}, {{ $order['warehouse']['address'] }}</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
