<div class="container py-8">
    <div class="row">
        @include('storefront.account.nav')

        <div class="col-md-9">
            <h1 class="title title-simple mb-4">My Orders</h1>

            @if (empty($orders))
                <p>You haven't placed any orders yet. <a href="{{ route('storefront.shop') }}">Start shopping</a>.</p>
            @else
                <table class="table">
                    <thead><tr><th>Order</th><th>Vendor</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order['order_number'] }}</td>
                                <td>{{ $order['vendor']['business_name'] ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($order['created_at'])->format('M d, Y') }}</td>
                                <td>{{ ucfirst($order['status']) }}</td>
                                <td>GHS {{ number_format($order['total_ghs'], 2) }}</td>
                                <td><a href="{{ route('storefront.account.orders.show', $order['id']) }}">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
