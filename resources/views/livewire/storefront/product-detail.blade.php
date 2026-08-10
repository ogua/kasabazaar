@if ($notFound)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
        <x-storefront.ui.empty-state
            icon="search"
            title="Product not found"
            description="This product may have been removed or is no longer available."
        >
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Continue Shopping</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    </div>
@else
    @php
        $price = $product['discount_price_ghs'] ?? $product['price_ghs'] ?? 0;
        $wasPrice = isset($product['discount_price_ghs']) ? ($product['price_ghs'] ?? null) : null;
        $images = $product['images'] ?? [];
        $inStock = ($product['stock'] ?? 0) > 0;
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <x-storefront.ui.breadcrumb :items="[
            ['label' => 'Shop', 'href' => route('storefront.shop')],
            ['label' => $product['name']],
        ]" />

        <div class="grid md:grid-cols-2 gap-10 mb-14">
            <div x-data="{ active: 0 }">
                <div class="aspect-square bg-surface-muted rounded-lg overflow-hidden mb-3">
                    @forelse ($images as $index => $image)
                        <img
                            x-show="active === {{ $index }}"
                            src="{{ $image['url'] }}"
                            alt="{{ $product['name'] }}"
                            class="w-full h-full object-cover"
                            onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
                        >
                    @empty
                        <img src="{{ asset('images/product-placeholder.png') }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover">
                    @endforelse
                </div>
                @if (count($images) > 1)
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($images as $index => $image)
                            <button
                                @click="active = {{ $index }}"
                                class="aspect-square rounded-sm overflow-hidden border-2 transition-colors"
                                :class="active === {{ $index }} ? 'border-accent' : 'border-transparent'"
                            >
                                <img src="{{ $image['url'] }}" alt="" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div x-data="{ quantity: {{ $quantity }}, adding: false }" @toast.window="adding = false">
                @if (! empty($product['vendor']['business_name']))
                    <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}" class="inline-flex items-center gap-1 text-sm text-navy-500 hover:text-accent mb-2">
                        Sold by {{ $product['vendor']['business_name'] }}
                    </a>
                @endif

                <h1 class="font-display font-bold text-2xl md:text-3xl text-navy-900 mb-3">{{ $product['name'] }}</h1>

                @if (($product['reviews_count'] ?? 0) > 0)
                    <div class="flex items-center gap-1.5 text-sm mb-4">
                        <div class="flex text-warning">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-storefront.icon :name="$i <= round($product['average_rating']) ? 'star' : 'star-outline'" class="w-4 h-4" />
                            @endfor
                        </div>
                        <span class="text-muted">{{ number_format($product['average_rating'], 1) }} ({{ $product['reviews_count'] }} reviews)</span>
                    </div>
                @endif

                <div class="flex items-baseline gap-3 mb-5">
                    @if ($wasPrice)
                        <span class="text-muted line-through tabular-nums">GHS {{ number_format($wasPrice, 2) }}</span>
                    @endif
                    <span class="font-display font-bold text-3xl text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</span>
                </div>

                <div class="prose prose-sm max-w-none text-muted mb-5 leading-relaxed">{!! nl2br(e($product['description'] ?? '')) !!}</div>

                <p class="mb-6">
                    @if ($inStock)
                        <x-storefront.ui.badge variant="success">In Stock &middot; {{ $product['stock'] }} available</x-storefront.ui.badge>
                    @else
                        <x-storefront.ui.badge variant="error">Out of Stock</x-storefront.ui.badge>
                    @endif
                </p>

                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center border border-border rounded-sm">
                        <button type="button" wire:click="decrement" class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted" aria-label="Decrease quantity">
                            <x-storefront.icon name="minus" class="w-4 h-4" />
                        </button>
                        <span class="w-10 text-center font-medium tabular-nums">{{ $quantity }}</span>
                        <button type="button" wire:click="increment" class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted" aria-label="Increase quantity">
                            <x-storefront.icon name="plus" class="w-4 h-4" />
                        </button>
                    </div>

                    <x-storefront.ui.button
                        variant="accent"
                        size="lg"
                        x-on:click="adding = true"
                        wire:click="addThisToCart"
                        :disabled="! $inStock"
                        x-bind:disabled="adding"
                        class="flex-1"
                    >
                        <template x-if="adding"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                        <template x-if="!adding"><x-storefront.icon name="cart" class="w-4 h-4" /></template>
                        {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                    </x-storefront.ui.button>

                    <button
                        type="button"
                        title="Add to wishlist"
                        wire:click="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"
                        class="flex items-center justify-center w-12 h-12 rounded-sm border border-border text-navy-900 hover:text-accent hover:border-accent shrink-0"
                    >
                        <x-storefront.icon name="heart" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <div
            wire:ignore
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

        <section
            wire:ignore
            x-data="{
                items: [],
                init() {
                    const all = JSON.parse(localStorage.getItem('recently_viewed_products') || '[]');
                    this.items = all.filter(p => p.id !== '{{ $product['id'] }}').slice(0, 4);
                }
            }"
            x-show="items.length"
            x-cloak
            class="mb-14"
        >
            <h2 class="font-display font-semibold text-xl text-navy-900 wake-underline mb-6">Recently Viewed</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <template x-for="item in items" :key="item.id">
                    <a :href="'/product/' + item.id" class="block bg-surface border border-border rounded-lg overflow-hidden">
                        <div class="aspect-square bg-surface-muted">
                            <img :src="item.image || '{{ asset('images/product-placeholder.png') }}'" :alt="item.name" class="w-full h-full object-cover" loading="lazy" onerror="this.src='{{ asset('images/product-placeholder.png') }}'">
                        </div>
                        <div class="p-3">
                            <p x-text="item.name" class="text-sm font-medium text-fg line-clamp-2 mb-1"></p>
                            <p x-text="'GHS ' + Number(item.price_ghs).toFixed(2)" class="font-display font-semibold text-navy-900 tabular-nums text-sm"></p>
                        </div>
                    </a>
                </template>
            </div>
        </section>

        <section class="max-w-3xl">
            <h2 class="font-display font-semibold text-xl text-navy-900 wake-underline mb-6">Reviews ({{ count($reviews) }})</h2>

            @forelse ($reviews as $review)
                <div class="border-b border-border py-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-semibold text-sm text-fg">{{ $review['user']['name'] ?? 'Customer' }}</span>
                        <div class="flex text-warning">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-storefront.icon :name="$i <= $review['rating'] ? 'star' : 'star-outline'" class="w-3.5 h-3.5" />
                            @endfor
                        </div>
                    </div>
                    @if (! empty($review['title']))
                        <p class="font-medium text-sm text-fg mb-1">{{ $review['title'] }}</p>
                    @endif
                    <p class="text-sm text-muted leading-relaxed">{{ $review['body'] }}</p>
                </div>
            @empty
                <p class="text-muted text-sm mb-6">No reviews yet — be the first to review this product.</p>
            @endforelse

            @auth
                <form
                    wire:submit.prevent="submitReview($refs.rating.value, $refs.title.value, $refs.body.value)"
                    x-data="{ submitting: false }"
                    @toast.window="submitting = false"
                    class="mt-6 space-y-4"
                >
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Rating</label>
                        <select x-ref="rating" class="border border-border rounded-sm px-3 py-2 text-sm max-w-32 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                            <option value="5">5 stars</option>
                            <option value="4">4 stars</option>
                            <option value="3">3 stars</option>
                            <option value="2">2 stars</option>
                            <option value="1">1 star</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Title (optional)</label>
                        <input x-ref="title" type="text" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-fg mb-1.5">Review</label>
                        <textarea x-ref="body" rows="3" placeholder="Share your thoughts..." class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                    </div>
                    <x-storefront.ui.button type="submit" variant="primary" x-on:click="submitting = true" x-bind:disabled="submitting">
                        <template x-if="submitting"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                        Submit Review
                    </x-storefront.ui.button>
                </form>
            @else
                <p class="text-sm text-muted"><a href="{{ route('storefront.login') }}" class="text-navy-500 hover:text-accent font-medium">Sign in</a> to leave a review.</p>
            @endauth
        </section>
    </div>
@endif
