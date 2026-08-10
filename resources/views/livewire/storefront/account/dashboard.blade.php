<x-storefront.account-layout title="Welcome back, {{ auth()->user()->name }}">
    <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Recent Orders</h2>

    @if ($error)
        <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
    @elseif (empty($recentOrders))
        <x-storefront.ui.empty-state icon="box" title="No orders yet" description="You haven't placed any orders yet.">
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
                        <th class="text-left px-4 py-3 font-semibold">Vendor</th>
                        <th class="text-left px-4 py-3 font-semibold">Status</th>
                        <th class="text-right px-4 py-3 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('storefront.account.orders.show', $order['order_group_id'] ?? $order['id']) }}" class="font-medium text-navy-900 hover:text-accent">{{ $order['order_number'] }}</a>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $order['vendor']['business_name'] ?? '—' }}</td>
                            <td class="px-4 py-3"><x-storefront.ui.badge>{{ ucfirst($order['status']) }}</x-storefront.ui.badge></td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">GHS {{ number_format($order['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-storefront.account-layout>
