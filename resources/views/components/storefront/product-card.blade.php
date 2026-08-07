@props(['product'])

@php
    $price = $product['discount_price_ghs'] ?? $product['price_ghs'] ?? null;
    $wasPrice = isset($product['discount_price_ghs']) ? ($product['price_ghs'] ?? null) : null;
    $image = $product['images'][0]['url'] ?? $product['primary_image_url'] ?? asset('storefront/assets/images/lazy.png');
@endphp

<div class="product text-center" wire:key="product-{{ $product['id'] }}">
    <figure class="product-media">
        <a href="{{ route('storefront.product', $product['id']) }}">
            <img src="{{ $image }}" alt="{{ $product['name'] }}" width="295" height="335" loading="lazy" />
        </a>

        @if ($wasPrice)
            <div class="product-label-group">
                <label class="product-label label-sale">Sale</label>
            </div>
        @endif

        <div class="product-action-vertical">
            <button type="button" class="btn-product-icon btn-cart w-icon-cart" title="Add to cart"
                    wire:click="$dispatch('add-to-cart', { productId: '{{ $product['id'] }}' })"></button>
            <button type="button" class="btn-product-icon btn-wishlist w-icon-heart" title="Wishlist"
                    wire:click="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"></button>
        </div>
    </figure>
    <div class="product-details">
        @if (! empty($product['vendor']['business_name']))
            <div class="product-cat">
                <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}">{{ $product['vendor']['business_name'] }}</a>
            </div>
        @endif
        <h3 class="product-name">
            <a href="{{ route('storefront.product', $product['id']) }}">{{ $product['name'] }}</a>
        </h3>
        <div class="product-price">
            @if ($wasPrice)
                <del>GHS {{ number_format($wasPrice, 2) }}</del>
            @endif
            GHS {{ number_format($price, 2) }}
        </div>
    </div>
</div>
