<div>
    <div class="row mb-6">
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="text-muted">Available Balance</div>
                <div class="h3 mb-0">GHS {{ number_format($summary['balance_ghs'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="text-muted">Pending Balance</div>
                <div class="h3 mb-0">GHS {{ number_format($summary['pending_balance_ghs'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stat-card">
                <div class="text-muted">Lifetime Earnings</div>
                <div class="h3 mb-0">GHS {{ number_format($summary['lifetime_earnings_ghs'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <h4>Recent Orders</h4>
        @if (empty($recentOrders))
            <p>No orders yet.</p>
        @else
            <table class="table">
                <thead><tr><th>Order</th><th>Status</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('vendor.orders.show', $order['id']) }}">{{ $order['order_number'] }}</a></td>
                            <td>{{ ucfirst($order['status']) }}</td>
                            <td>GHS {{ number_format($order['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
