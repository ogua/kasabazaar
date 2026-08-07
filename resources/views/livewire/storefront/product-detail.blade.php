@php
    $price = $product['discount_price_ghs'] ?? $product['price_ghs'] ?? null;
    $wasPrice = isset($product['discount_price_ghs']) ? ($product['price_ghs'] ?? null) : null;
    $images = $product['images'] ?? [];
@endphp

<div class="container mt-6 mb-8">
    <div class="row">
        <div class="col-md-6 mb-4" wire:ignore>
            <div class="product-gallery">
                @forelse ($images as $image)
                    <figure class="product-image mb-2">
                        <img src="{{ $image['url'] }}" alt="{{ $product['name'] }}" class="w-100" data-zoom="{{ $image['url'] }}" />
                    </figure>
                @empty
                    <figure class="product-image mb-2">
                        <img src="{{ asset('storefront/assets/images/lazy.png') }}" alt="{{ $product['name'] }}" class="w-100" />
                    </figure>
                @endforelse
            </div>
        </div>

        <div class="col-md-6">
            @if (! empty($product['vendor']['business_name']))
                <div class="product-cat mb-2">
                    Sold by <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}">{{ $product['vendor']['business_name'] }}</a>
                </div>
            @endif

            <h1 class="product-title">{{ $product['name'] }}</h1>

            @if (($product['reviews_count'] ?? 0) > 0)
                <div class="mb-2">
                    {{ number_format($product['average_rating'], 1) }} ★ ({{ $product['reviews_count'] }} reviews)
                </div>
            @endif

            <div class="product-price mb-4">
                @if ($wasPrice)
                    <del class="mr-2">GHS {{ number_format($wasPrice, 2) }}</del>
                @endif
                <span class="h3">GHS {{ number_format($price, 2) }}</span>
            </div>

            <div class="product-desc mb-4">{!! nl2br(e($product['description'] ?? '')) !!}</div>

            <p class="mb-4">
                @if (($product['stock'] ?? 0) > 0)
                    <span class="text-success">In Stock ({{ $product['stock'] }} available)</span>
                @else
                    <span class="text-danger">Out of Stock</span>
                @endif
            </p>

            <div class="d-flex align-items-center mb-6" style="gap:12px;">
                <div class="quantity-selector d-flex align-items-center">
                    <button type="button" class="btn btn-outline" wire:click="decrement">−</button>
                    <span class="mx-3">{{ $quantity }}</span>
                    <button type="button" class="btn btn-outline" wire:click="increment">+</button>
                </div>
                <button type="button" class="btn btn-primary btn-rounded" wire:click="addThisToCart" @disabled(($product['stock'] ?? 0) < 1)>
                    <i class="w-icon-cart"></i> Add to Cart
                </button>
                <button type="button" class="btn-product-icon btn-wishlist w-icon-heart"
                        wire:click="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"></button>
            </div>
        </div>
    </div>

    <div wire:ignore
         x-data="{
             init() {
                 const key = 'recently_viewed_products';
                 const item = { id: '{{ $product['id'] }}', name: @js($product['name']), price_ghs: {{ $price }}, image: @js($images[0]['url'] ?? null) };
                 let list = JSON.parse(localStorage.getItem(key) || '[]').filter(p => p.id !== item.id);
                 list.unshift(item);
                 localStorage.setItem(key, JSON.stringify(list.slice(0, 8)));
             }
         }"
    ></div>

    <div id="recently-viewed" class="mt-8" wire:ignore
         x-data="{
             items: [],
             init() {
                 const all = JSON.parse(localStorage.getItem('recently_viewed_products') || '[]');
                 this.items = all.filter(p => p.id !== '{{ $product['id'] }}').slice(0, 4);
             }
         }"
         x-show="items.length"
    >
        <h3 class="title title-simple">Recently Viewed</h3>
        <div class="row">
            <template x-for="item in items" :key="item.id">
                <div class="col-6 col-md-3 mb-4">
                    <a :href="'/product/' + item.id" class="d-block text-center">
                        <img :src="item.image || '{{ asset('storefront/assets/images/lazy.png') }}'" :alt="item.name" width="150" height="170" style="object-fit:cover;">
                        <div x-text="item.name" class="mt-2"></div>
                        <div x-text="'GHS ' + Number(item.price_ghs).toFixed(2)"></div>
                    </a>
                </div>
            </template>
        </div>
    </div>

    <div class="product-reviews mt-8">
        <h3 class="title title-simple">Reviews ({{ count($reviews) }})</h3>

        @forelse ($reviews as $review)
            <div class="review-item border-bottom py-4">
                <strong>{{ $review['user']['name'] ?? 'Customer' }}</strong> — {{ $review['rating'] }} ★
                @if (!empty($review['title']))<div class="font-weight-bold">{{ $review['title'] }}</div>@endif
                <p class="mb-0">{{ $review['body'] }}</p>
            </div>
        @empty
            <p>No reviews yet — be the first to review this product.</p>
        @endforelse

        @auth
            <form wire:submit.prevent="submitReview($refs.rating.value, $refs.title.value, $refs.body.value)" class="mt-6" x-data>
                <div class="form-group">
                    <label>Rating</label>
                    <select x-ref="rating" class="form-control" style="max-width:120px;">
                        <option value="5">5 ★</option>
                        <option value="4">4 ★</option>
                        <option value="3">3 ★</option>
                        <option value="2">2 ★</option>
                        <option value="1">1 ★</option>
                    </select>
                </div>
                <div class="form-group">
                    <input x-ref="title" type="text" class="form-control" placeholder="Review title (optional)" />
                </div>
                <div class="form-group">
                    <textarea x-ref="body" class="form-control" rows="3" placeholder="Share your thoughts…"></textarea>
                </div>
                <button type="submit" class="btn btn-dark">Submit Review</button>
            </form>
        @else
            <p><a href="{{ route('storefront.login') }}">Sign in</a> to leave a review.</p>
        @endauth
    </div>
</div>
