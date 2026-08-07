<div class="stat-card">
    <table class="table">
        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th></th></tr></thead>
        <tbody>
            @forelse ($orders as $order)
                <tr wire:key="vo-{{ $order['id'] }}">
                    <td>{{ $order['order_number'] }}</td>
                    <td>{{ $order['user']['name'] ?? '—' }}</td>
                    <td>{{ ucfirst($order['status']) }}</td>
                    <td>GHS {{ number_format($order['total_ghs'], 2) }}</td>
                    <td><a href="{{ route('vendor.orders.show', $order['id']) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
