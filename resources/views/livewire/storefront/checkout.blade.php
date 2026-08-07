@php $items = $cart['items'] ?? []; @endphp

<div class="container py-8">
    <h1 class="title title-simple mb-6">Checkout</h1>

    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="row">
        <div class="col-md-7">
            <h3>Delivery Address</h3>

            @foreach ($addresses as $address)
                <label class="d-block border rounded p-3 mb-2">
                    <input type="radio" wire:model="selectedAddressId" value="{{ $address['id'] }}" />
                    <strong>{{ $address['full_name'] }}</strong> — {{ $address['phone'] }}<br>
                    {{ $address['street'] }}, {{ $address['city'] }}, {{ $address['region'] }}
                </label>
            @endforeach

            @if ($showNewAddressForm)
                <div class="border rounded p-3 mt-4">
                    <h4>New Address</h4>
                    <div class="form-group"><input class="form-control" placeholder="Full name" wire:model="full_name"></div>
                    <div class="form-group"><input class="form-control" placeholder="Phone" wire:model="phone"></div>
                    <div class="form-group"><input class="form-control" placeholder="Region (e.g. Greater Accra)" wire:model="region"></div>
                    <div class="form-group"><input class="form-control" placeholder="City" wire:model="city"></div>
                    <div class="form-group"><input class="form-control" placeholder="Street / landmark" wire:model="street"></div>
                    <button type="button" class="btn btn-dark" wire:click="saveAddress">Save Address</button>
                </div>
            @else
                <button type="button" class="btn btn-link" wire:click="$set('showNewAddressForm', true)">+ Add a new address</button>
            @endif

            <div class="form-group mt-4">
                <label>Order Notes (optional)</label>
                <textarea class="form-control" wire:model="notes" rows="2"></textarea>
            </div>
        </div>

        <div class="col-md-5">
            <div class="border rounded p-4">
                <h3>Order Summary</h3>
                @foreach ($items as $item)
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $item['product']['name'] ?? 'Product' }} × {{ $item['quantity'] }}</span>
                        <span>GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</span>
                    </div>
                @endforeach
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Subtotal</strong>
                    <strong>GHS {{ number_format($cart['subtotal_ghs'] ?? 0, 2) }}</strong>
                </div>
                <p class="text-muted small mt-2">Shipping and tax are calculated per vendor and shown on your order confirmation.</p>

                <button type="button" class="btn btn-primary btn-block btn-rounded mt-4" wire:click="placeOrder" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="placeOrder">Place Order &amp; Pay</span>
                    <span wire:loading wire:target="placeOrder">Placing order…</span>
                </button>
            </div>
        </div>
    </div>
</div>
