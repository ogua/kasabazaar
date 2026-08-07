<div class="container py-8">
    <div class="row">
        @include('storefront.account.nav')

        <div class="col-md-9">
            <h1 class="title title-simple mb-4">Welcome back, {{ auth()->user()->name }}</h1>

            <h3>Recent Orders</h3>
            @if (empty($recentOrders))
                <p>You haven't placed any orders yet. <a href="{{ route('storefront.shop') }}">Start shopping</a>.</p>
            @else
                <table class="table">
                    <thead><tr><th>Order</th><th>Vendor</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('storefront.account.orders.show', $order['order_group_id'] ?? $order['id']) }}">{{ $order['order_number'] }}</a></td>
                                <td>{{ $order['vendor']['business_name'] ?? '—' }}</td>
                                <td>{{ ucfirst($order['status']) }}</td>
                                <td>GHS {{ number_format($order['total_ghs'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
