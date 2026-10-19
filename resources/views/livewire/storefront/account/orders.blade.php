<x-storefront.account-layout title="My Orders">
    <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="font-display font-semibold text-lg text-navy-900">Order History</h2>
        <label for="orders-status" class="sr-only">Filter by status</label>
        <select id="orders-status" wire:model.live="status" class="border border-border rounded-sm px-3 py-2 text-sm max-w-44 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
            <option value="">All Statuses</option>
            {{-- Values match the backend's EcommerceOrderStatus enum. --}}
            @foreach (['awaiting_payment', 'paid', 'processing', 'packed', 'dispatched', 'in_transit', 'delivered', 'cancelled', 'refunded'] as $statusOption)
                <option value="{{ $statusOption }}">{{ Str::headline($statusOption) }}</option>
            @endforeach
        </select>
    </div>

    @if ($error)
        <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
    @else
        <div wire:loading.class="opacity-50" wire:target="status,gotoPage,previousPage,nextPage">
            @if (empty($orders))
                <x-storefront.ui.empty-state icon="box" title="No orders found" description="You haven't placed any orders yet.">
                    <x-slot:action>
                        <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Start Shopping</x-storefront.ui.button>
                    </x-slot:action>
                </x-storefront.ui.empty-state>
            @else
                <div class="border border-border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-muted text-xs uppercase tracking-wide text-muted">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold">Order</th>
                                <th class="text-left px-4 py-3 font-semibold hidden sm:table-cell">Vendor</th>
                                <th class="text-left px-4 py-3 font-semibold hidden sm:table-cell">Date</th>
                                <th class="text-left px-4 py-3 font-semibold">Status</th>
                                <th class="text-right px-4 py-3 font-semibold">Total</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-fg">{{ $order['order_number'] }}</td>
                                    <td class="px-4 py-3 text-muted hidden sm:table-cell">{{ $order['vendor']['business_name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-muted hidden sm:table-cell">{{ \Illuminate\Support\Carbon::parse($order['created_at'])->format('M d, Y') }}</td>
                                    @php $orderAwaitingPayment = $order['status'] === 'awaiting_payment' && ($order['payment_status'] ?? null) === 'pending'; @endphp
                                    <td class="px-4 py-3"><x-storefront.ui.badge :variant="$orderAwaitingPayment ? 'warning' : 'default'">{{ Str::headline($order['status']) }}</x-storefront.ui.badge></td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums">GHS {{ number_format($order['total_ghs'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('storefront.account.orders.show', $order['id']) }}" class="font-medium {{ $orderAwaitingPayment ? 'text-accent hover:text-accent-hover' : 'text-navy-500 hover:text-accent' }}">{{ $orderAwaitingPayment ? 'Pay now' : 'View' }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-storefront.ui.pagination :meta="$meta" />
            @endif
        </div>
    @endif
</x-storefront.account-layout>
