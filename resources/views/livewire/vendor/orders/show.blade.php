<div class="stat-card">
    <p>Status: <strong>{{ ucfirst($order['status']) }}</strong> — Customer: {{ $order['user']['name'] ?? '—' }}</p>

    <div class="mb-4" style="display:flex;gap:8px;">
        @if ($order['status'] === 'paid')
            <button type="button" class="btn btn-dark btn-sm" wire:click="process">Start Processing</button>
        @endif
        @if ($order['status'] === 'processing')
            <button type="button" class="btn btn-dark btn-sm" wire:click="pack">Mark Packed</button>
        @endif
        @if ($order['status'] === 'packed' && empty($order['shipment']))
            <input type="text" class="form-control d-inline-block" style="width:200px;" placeholder="Courier name" wire:model="courier">
            <button type="button" class="btn btn-dark btn-sm" wire:click="createShipment">Create Shipment</button>
        @endif
        @if ($order['status'] === 'packed' && !empty($order['shipment']))
            <button type="button" class="btn btn-dark btn-sm" wire:click="dispatchOrder">Mark Dispatched</button>
        @endif
    </div>

    <table class="table">
        <thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
            @foreach ($order['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>GHS {{ number_format($item['total_ghs'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Total: GHS {{ number_format($order['total_ghs'], 2) }}</strong></p>

    @if (!empty($order['delivery_detail']))
        <h4>Delivery Address</h4>
        <p>{{ $order['delivery_detail']['full_name'] }}, {{ $order['delivery_detail']['phone'] }}<br>
        {{ $order['delivery_detail']['street'] }}, {{ $order['delivery_detail']['city'] }}, {{ $order['delivery_detail']['region'] }}</p>
    @endif
</div>
