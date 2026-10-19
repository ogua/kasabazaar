@props(['product'])

@php
    $regularPrice = (float) ($product['price_ghs'] ?? 0);
    $hasDiscount = isset($product['discount_price_ghs']) && (float) $product['discount_price_ghs'] < $regularPrice;
    $price = $hasDiscount ? (float) $product['discount_price_ghs'] : $regularPrice;
    $wasPrice = $hasDiscount ? $regularPrice : null;
    $image = $product['images'][0]['url'] ?? $product['primary_image_url'] ?? null;
    $discountPct = $wasPrice && $wasPrice > 0 ? (int) round((($wasPrice - $price) / $wasPrice) * 100) : null;
    $reviewsCount = (int) ($product['reviews_count'] ?? 0);
    $rating = (float) ($product['average_rating'] ?? 0);
    // Stock is only judged when the API sends it — an absent key is not "sold out".
    $stock = isset($product['stock']) ? (int) $product['stock'] : null;
    $soldOut = $stock !== null && $stock <= 0;
    $lowStock = $stock !== null && $stock > 0 && $stock <= 5;
@endphp

<div
    x-data="{ addingToCart: false, togglingWishlist: false }"
    @toast.window="addingToCart = false; togglingWishlist = false"
    class="group relative flex flex-col bg-surface border border-border rounded-lg overflow-hidden hover:border-navy-200 hover:shadow-float transition duration-150"
    wire:key="product-{{ $product['id'] }}"
>
    <a href="{{ route('storefront.product', $product['id']) }}" class="relative block aspect-square bg-surface-muted overflow-hidden" tabindex="-1" aria-hidden="true">
        <img
            src="{{ $image ?? asset('images/product-placeholder.png') }}"
            alt=""
            loading="lazy"
            decoding="async"
            width="400"
            height="400"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 {{ $soldOut ? 'opacity-60' : '' }}"
            onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
        >
        @if ($discountPct)
            <span class="absolute top-2 left-2 rounded-sm bg-accent px-1.5 py-0.5 text-[11px] font-bold text-white tabular-nums">-{{ $discountPct }}%</span>
        @endif
        @if ($soldOut)
            <span class="absolute inset-x-0 bottom-0 bg-navy-950/75 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wide text-white">Out of stock</span>
        @endif
    </a>

    {{-- Siblings of the image link, not children: a button inside <a> is invalid and unreachable by keyboard. --}}
    <div class="absolute top-2 right-2 flex flex-col gap-2">

        <button
            type="button"
            title="Add to wishlist"
            aria-label="Add {{ $product['name'] }} to wishlist"
            x-on:click="togglingWishlist = true"
            wire:click="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"
            :disabled="togglingWishlist"
            class="flex items-center justify-center w-8 h-8 rounded-full bg-white/95 text-navy-900 shadow-sm hover:text-accent disabled:opacity-60"
        >
            <template x-if="togglingWishlist"><x-storefront.ui.spinner class="w-4 h-4" /></template>
            <template x-if="!togglingWishlist"><x-storefront.icon name="heart" class="w-4 h-4" /></template>
        </button>

        <button
            type="button"
            title="Compare"
            aria-label="Compare {{ $product['name'] }}"
            x-on:click="Livewire.dispatch('toggle-compare', { productId: @js($product['id']), name: @js($product['name']), image: @js($image) })"
            :aria-pressed="$store.compare.has(@js($product['id']))"
            class="flex items-center justify-center w-8 h-8 rounded-full shadow-sm transition-colors"
            :class="$store.compare.has(@js($product['id'])) ? 'bg-navy-900 text-white' : 'bg-white/95 text-navy-900 hover:text-accent'"
        >
            <x-storefront.icon name="arrows-right-left" class="w-4 h-4" />
        </button>
    </div>

    <div class="flex flex-col flex-1 p-3">
        @if (! empty($product['vendor']['business_name']))
            <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}" class="text-[11px] uppercase tracking-wide text-muted hover:text-accent mb-1 truncate">
                {{ $product['vendor']['business_name'] }}
            </a>
        @endif

        <h3 class="text-sm text-fg leading-snug line-clamp-2 min-h-10">
            <a href="{{ route('storefront.product', $product['id']) }}" class="hover:text-accent">{{ $product['name'] }}</a>
        </h3>

        @if ($reviewsCount > 0)
            <div class="flex items-center gap-1 mt-1.5 text-xs text-muted" aria-label="Rated {{ number_format($rating, 1) }} out of 5 from {{ $reviewsCount }} reviews">
                <span class="flex text-warning" aria-hidden="true">
                    @for ($i = 1; $i <= 5; $i++)
                        <x-storefront.icon :name="$i <= round($rating) ? 'star' : 'star-outline'" class="w-3.5 h-3.5" />
                    @endfor
                </span>
                <span class="tabular-nums">({{ $reviewsCount }})</span>
            </div>
        @endif

        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 leading-tight">
            <span class="font-display font-bold text-base text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</span>
            @if ($wasPrice)
                <span class="text-xs text-muted line-through tabular-nums">GHS {{ number_format($wasPrice, 2) }}</span>
            @endif
        </div>

        @if ($lowStock)
            <p class="mt-1 text-[11px] font-medium text-warning">Only {{ $stock }} left</p>
        @endif

        <div class="mt-auto pt-3">
            <button
                type="button"
                x-on:click="addingToCart = true"
                wire:click="$dispatch('add-to-cart', { productId: '{{ $product['id'] }}' })"
                :disabled="addingToCart || @js($soldOut)"
                aria-label="{{ $soldOut ? 'Sold out' : 'Add '.$product['name'].' to cart' }}"
                class="flex items-center justify-center gap-2 h-9 w-full rounded-sm border border-navy-900 text-navy-900 text-xs font-semibold uppercase tracking-wide hover:bg-navy-900 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-50 disabled:pointer-events-none transition-colors"
            >
                <template x-if="addingToCart"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                <template x-if="!addingToCart"><x-storefront.icon name="cart" class="w-4 h-4" /></template>
                {{ $soldOut ? 'Sold out' : 'Add to cart' }}
            </button>
        </div>
    </div>
</div>
