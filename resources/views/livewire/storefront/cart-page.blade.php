@php $items = $cart['items'] ?? []; @endphp

<div class="container py-8">
    <h1 class="title title-simple mb-6">Shopping Cart</h1>

    @if (empty($items))
        <p>Your cart is empty. <a href="{{ route('storefront.shop') }}">Continue shopping</a>.</p>
    @else
        <table class="table table-cart">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr wire:key="cart-item-{{ $item['id'] }}">
                        <td>{{ $item['product']['name'] ?? 'Product' }}</td>
                        <td>{{ $item['vendor']['business_name'] ?? '—' }}</td>
                        <td>GHS {{ number_format($item['price_ghs'], 2) }}</td>
                        <td style="max-width:100px;">
                            <input type="number" min="1" class="form-control" value="{{ $item['quantity'] }}"
                                   wire:change="updateQuantity('{{ $item['id'] }}', $event.target.value)" />
                        </td>
                        <td>GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</td>
                        <td>
                            <button type="button" class="btn btn-link text-danger" wire:click="removeItem('{{ $item['id'] }}')">Remove</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-6">
            <div class="col-md-6">
                <form wire:submit.prevent="applyCoupon" class="d-flex" style="gap:8px;">
                    <input type="text" class="form-control" wire:model="couponCode" placeholder="Coupon code">
                    <button type="submit" class="btn btn-outline-dark">Apply</button>
                </form>
                @if ($couponError)
                    <p class="text-danger mt-2">{{ $couponError }}</p>
                @endif
            </div>
            <div class="col-md-6 text-md-right">
                <p>Subtotal: <strong>GHS {{ number_format($cart['subtotal_ghs'] ?? 0, 2) }}</strong></p>
                @if (($cart['discount_ghs'] ?? 0) > 0)
                    <p>Discount: <strong>-GHS {{ number_format($cart['discount_ghs'], 2) }}</strong></p>
                @endif
                <a href="{{ route('storefront.checkout') }}" class="btn btn-primary btn-rounded">Proceed to Checkout</a>
            </div>
        </div>
    @endif
</div>
