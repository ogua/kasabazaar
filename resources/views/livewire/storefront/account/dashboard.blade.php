<x-storefront.account-layout title="Welcome back, {{ auth()->user()->name }}">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @foreach ([
            ['label' => 'Orders', 'desc' => 'Track and manage', 'route' => 'storefront.account.orders', 'icon' => 'box'],
            ['label' => 'My Auctions', 'desc' => 'Bids and wins', 'route' => 'storefront.account.auctions', 'icon' => 'gavel'],
            ['label' => 'Wishlist', 'desc' => 'Saved for later', 'route' => 'storefront.wishlist', 'icon' => 'heart'],
            ['label' => 'Addresses', 'desc' => 'Delivery details', 'route' => 'storefront.account.addresses', 'icon' => 'map-pin'],
        ] as $tile)
            <a href="{{ route($tile['route']) }}" class="group flex items-center gap-3 rounded-lg border border-border p-3 hover:border-navy-200 hover:bg-surface-muted transition-colors">
                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-accent-soft text-accent shrink-0">
                    <x-storefront.icon :name="$tile['icon']" class="w-5 h-5" />
                </span>
                <span class="leading-tight min-w-0">
                    <span class="block text-sm font-semibold text-fg group-hover:text-accent">{{ $tile['label'] }}</span>
                    <span class="block text-xs text-muted truncate">{{ $tile['desc'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Recent orders</h2>

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
