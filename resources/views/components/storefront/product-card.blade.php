@props(['product'])

@php
    $price = $product['discount_price_ghs'] ?? $product['price_ghs'] ?? 0;
    $wasPrice = isset($product['discount_price_ghs']) ? ($product['price_ghs'] ?? null) : null;
    $image = $product['images'][0]['url'] ?? $product['primary_image_url'] ?? null;
    $discountPct = $wasPrice && $wasPrice > 0 ? (int) round((($wasPrice - $price) / $wasPrice) * 100) : null;
@endphp

<div
    x-data="{ addingToCart: false, togglingWishlist: false }"
    @toast.window="addingToCart = false; togglingWishlist = false"
    class="group relative flex flex-col bg-surface border border-border rounded-lg overflow-hidden hover:shadow-float transition-shadow duration-150"
    wire:key="product-{{ $product['id'] }}"
>
    <a href="{{ route('storefront.product', $product['id']) }}" class="relative block aspect-square bg-surface-muted overflow-hidden">
        <img
            src="{{ $image ?? asset('images/product-placeholder.png') }}"
            alt="{{ $product['name'] }}"
            loading="lazy"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
        >
        @if ($discountPct)
            <x-storefront.ui.badge variant="accent" class="absolute top-3 left-3">-{{ $discountPct }}%</x-storefront.ui.badge>
        @endif

        <button
            type="button"
            title="Add to wishlist"
            x-on:click.prevent.stop="togglingWishlist = true"
            wire:click.prevent.stop="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"
            :disabled="togglingWishlist"
            class="absolute top-3 right-3 flex items-center justify-center w-9 h-9 rounded-full bg-white/95 text-navy-900 shadow-sm hover:text-accent disabled:opacity-60"
        >
            <template x-if="togglingWishlist"><x-storefront.ui.spinner class="w-4 h-4" /></template>
            <template x-if="!togglingWishlist"><x-storefront.icon name="heart" class="w-4 h-4" /></template>
        </button>
    </a>

    <div class="flex flex-col flex-1 p-4">
        @if (! empty($product['vendor']['business_name']))
            <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}" class="text-xs text-muted hover:text-accent mb-1 truncate">
                {{ $product['vendor']['business_name'] }}
            </a>
        @endif

        <h3 class="text-sm font-medium text-fg leading-snug mb-2 line-clamp-2">
            <a href="{{ route('storefront.product', $product['id']) }}" class="hover:text-accent">{{ $product['name'] }}</a>
        </h3>

        <div class="mt-auto flex items-end justify-between gap-2 pt-1">
            <div class="leading-tight">
                @if ($wasPrice)
                    <div class="text-xs text-muted line-through tabular-nums">GHS {{ number_format($wasPrice, 2) }}</div>
                @endif
                <div class="font-display font-semibold text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</div>
            </div>

            <button
                type="button"
                title="Add to cart"
                x-on:click="addingToCart = true"
                wire:click="$dispatch('add-to-cart', { productId: '{{ $product['id'] }}' })"
                :disabled="addingToCart"
                class="flex items-center justify-center w-10 h-10 rounded-sm bg-navy-900 text-white hover:bg-accent disabled:opacity-60 shrink-0"
            >
                <template x-if="addingToCart"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                <template x-if="!addingToCart"><x-storefront.icon name="cart" class="w-4 h-4" /></template>
            </button>
        </div>
    </div>
</div>
