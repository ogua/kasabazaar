@php $cancellable = in_array($order['status'], ['pending', 'awaiting_payment']); @endphp

<div class="container py-8">
    <div class="row">
        @include('storefront.account.nav')

        <div class="col-md-9">
            <h1 class="title title-simple mb-2">Order {{ $order['order_number'] }}</h1>
            <p>Status: <strong>{{ ucfirst($order['status']) }}</strong> — Sold by {{ $order['vendor']['business_name'] ?? '—' }}</p>

            @if ($cancellable)
                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="cancel" wire:confirm="Cancel this order?">Cancel Order</button>
            @endif

            <table class="table mt-4">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($order['items'] ?? [] as $item)
                        <tr>
                            <td>{{ $item['product_name'] }}</td>
                            <td>{{ $item['quantity'] }}</td>
                            <td>GHS {{ number_format($item['unit_price_ghs'], 2) }}</td>
                            <td>GHS {{ number_format($item['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="text-right">
                <p>Subtotal: GHS {{ number_format($order['subtotal_ghs'], 2) }}</p>
                <p>Shipping: GHS {{ number_format($order['shipping_fee_ghs'], 2) }}</p>
                <p>Tax: GHS {{ number_format($order['tax_ghs'] ?? 0, 2) }}</p>
                @if (($order['discount_ghs'] ?? 0) > 0)
                    <p>Discount: -GHS {{ number_format($order['discount_ghs'], 2) }}</p>
                @endif
                <p><strong>Total: GHS {{ number_format($order['total_ghs'], 2) }}</strong></p>
            </div>

            @if (!empty($order['delivery_detail']))
                <h4 class="mt-6">Delivery Address</h4>
                <p>
                    {{ $order['delivery_detail']['full_name'] }}, {{ $order['delivery_detail']['phone'] }}<br>
                    {{ $order['delivery_detail']['street'] }}, {{ $order['delivery_detail']['city'] }}, {{ $order['delivery_detail']['region'] }}
                </p>
            @endif

            @if ($order['status'] === 'delivered' && empty($order['rating']))
                <div class="mt-6">
                    @if ($rated)
                        <p class="text-success">Thanks for rating this order!</p>
                    @else
                        <h4>Rate this order</h4>
                        <select wire:model="ratingValue" class="form-control mb-2" style="max-width:120px;">
                            <option value="5">5 ★</option>
                            <option value="4">4 ★</option>
                            <option value="3">3 ★</option>
                            <option value="2">2 ★</option>
                            <option value="1">1 ★</option>
                        </select>
                        <textarea wire:model="ratingComment" class="form-control mb-2" rows="2" placeholder="Comments (optional)"></textarea>
                        <button type="button" class="btn btn-dark" wire:click="submitRating">Submit Rating</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
