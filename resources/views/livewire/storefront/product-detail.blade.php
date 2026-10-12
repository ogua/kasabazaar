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
        $discountPct = $wasPrice && $wasPrice > 0 ? (int) round((($wasPrice - $price) / $wasPrice) * 100) : null;
        $images = $product['images'] ?? [];
        $inStock = ($product['stock'] ?? 0) > 0;
        $reviewsCount = (int) ($product['reviews_count'] ?? 0);
        $vendor = $product['vendor'] ?? [];
    @endphp

    <div class="bg-surface-muted pb-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
            <x-storefront.ui.breadcrumb :items="[
                ['label' => 'Shop', 'href' => route('storefront.shop')],
                ['label' => $product['name']],
            ]" />

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px] items-start">
                {{-- Gallery + buy panel --}}
                <div class="bg-surface border border-border rounded-lg p-4 sm:p-6 grid md:grid-cols-2 gap-6 md:gap-8">
                    <div x-data="{ active: 0 }">
                        <div class="relative aspect-square bg-surface-muted rounded-lg overflow-hidden mb-3">
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
                            @if ($discountPct)
                                <span class="absolute top-3 left-3 rounded-sm bg-accent px-2 py-1 text-xs font-bold text-white tabular-nums">-{{ $discountPct }}%</span>
                            @endif
                        </div>
                        @if (count($images) > 1)
                            <div class="grid grid-cols-5 gap-2">
                                @foreach ($images as $index => $image)
                                    <button
                                        type="button"
                                        @click="active = {{ $index }}"
                                        class="aspect-square rounded-sm overflow-hidden border-2 transition-colors"
                                        :class="active === {{ $index }} ? 'border-accent' : 'border-border hover:border-navy-200'"
                                        aria-label="Show image {{ $index + 1 }}"
                                    >
                                        <img src="{{ $image['url'] }}" alt="" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-data="{ quantity: {{ $quantity }}, adding: false }" @toast.window="adding = false" class="flex flex-col">
                        @if (! empty($vendor['business_name']))
                            <a href="{{ route('storefront.vendor', $vendor['slug']) }}" class="self-start text-xs uppercase tracking-wide text-muted hover:text-accent mb-2">
                                {{ $vendor['business_name'] }}
                            </a>
                        @endif

                        <h1 class="font-display font-bold text-2xl md:text-3xl leading-tight text-navy-900 mb-3 text-balance">{{ $product['name'] }}</h1>

                        @if ($reviewsCount > 0)
                            <a href="#reviews" class="flex items-center gap-1.5 text-sm mb-4 self-start group">
                                <span class="flex text-warning" aria-hidden="true">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-storefront.icon :name="$i <= round($product['average_rating']) ? 'star' : 'star-outline'" class="w-4 h-4" />
                                    @endfor
                                </span>
                                <span class="text-muted group-hover:text-accent">{{ number_format($product['average_rating'], 1) }} · {{ $reviewsCount }} {{ Str::plural('review', $reviewsCount) }}</span>
                            </a>
                        @endif

                        <div class="border-y border-border py-4 mb-5">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span class="font-display font-bold text-3xl text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</span>
                                @if ($wasPrice)
                                    <span class="text-muted line-through tabular-nums">GHS {{ number_format($wasPrice, 2) }}</span>
                                    <span class="rounded-sm bg-accent-soft px-1.5 py-0.5 text-xs font-semibold text-accent-hover tabular-nums">Save GHS {{ number_format($wasPrice - $price, 2) }}</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm">
                                @if ($inStock)
                                    <span class="inline-flex items-center gap-1.5 font-medium text-success">
                                        <x-storefront.icon name="check-circle" class="w-4 h-4" />
                                        In stock
                                    </span>
                                    <span class="text-muted">· {{ $product['stock'] }} available</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-medium text-error">
                                        <x-storefront.icon name="exclamation-circle" class="w-4 h-4" />
                                        Out of stock
                                    </span>
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-sm font-medium text-fg">Quantity</span>
                            <div class="flex items-center border border-border rounded-sm">
                                <button type="button" wire:click="decrement" class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted" aria-label="Decrease quantity">
                                    <x-storefront.icon name="minus" class="w-4 h-4" />
                                </button>
                                <span class="w-10 text-center font-medium tabular-nums">{{ $quantity }}</span>
                                <button type="button" wire:click="increment" class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted" aria-label="Increase quantity">
                                    <x-storefront.icon name="plus" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-storefront.ui.button
                                variant="accent"
                                size="lg"
                                x-on:click="adding = true"
                                wire:click="addThisToCart"
                                :disabled="! $inStock"
                                x-bind:disabled="adding"
                                class="flex-1 h-12"
                            >
                                <template x-if="adding"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                                <template x-if="!adding"><x-storefront.icon name="cart" class="w-5 h-5" /></template>
                                {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                            </x-storefront.ui.button>

                            <button
                                type="button"
                                title="Add to wishlist"
                                aria-label="Add to wishlist"
                                wire:click="$dispatch('toggle-wishlist', { productId: '{{ $product['id'] }}' })"
                                class="flex items-center justify-center w-12 h-12 rounded-sm border border-border text-navy-900 hover:text-accent hover:border-accent shrink-0"
                            >
                                <x-storefront.icon name="heart" class="w-5 h-5" />
                            </button>

                            <button
                                type="button"
                                title="Compare"
                                aria-label="Compare this product"
                                x-on:click="Livewire.dispatch('toggle-compare', { productId: @js($product['id']), name: @js($product['name']), image: @js($images[0]['url'] ?? null) })"
                                :aria-pressed="$store.compare.has(@js($product['id']))"
                                class="flex items-center justify-center w-12 h-12 rounded-sm border shrink-0 transition-colors"
                                :class="$store.compare.has(@js($product['id'])) ? 'border-navy-900 bg-navy-900 text-white' : 'border-border text-navy-900 hover:text-accent hover:border-accent'"
                            >
                                <x-storefront.icon name="arrows-right-left" class="w-5 h-5" />
                            </button>
                        </div>

                        @php
                            $shareUrl = route('storefront.product', $product['id']);
                            $shareText = $product['name'].' — GHS '.number_format($price, 2).' on '.config('app.name');
                        @endphp
                        <div
                            class="mt-4 flex flex-wrap items-center gap-2 text-sm"
                            x-data="{
                                copied: false,
                                async share() {
                                    if (navigator.share) {
                                        try { await navigator.share({ title: @js($product['name']), text: @js($shareText), url: @js($shareUrl) }); } catch (e) {}
                                        return;
                                    }
                                    await navigator.clipboard?.writeText(@js($shareUrl));
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                },
                            }"
                        >
                            <span class="text-muted mr-1">Share:</span>
                            <a href="https://wa.me/?text={{ urlencode($shareText.' '.$shareUrl) }}" target="_blank" rel="noopener" class="flex items-center justify-center w-9 h-9 rounded-full bg-[#25D366] text-white hover:opacity-90" aria-label="Share on WhatsApp">
                                <x-storefront.icon name="whatsapp" class="w-4 h-4" />
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex items-center justify-center w-9 h-9 rounded-full bg-[#1877F2] text-white hover:opacity-90" aria-label="Share on Facebook">
                                <x-storefront.icon name="facebook" class="w-4 h-4" />
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($shareText) }}&url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex items-center justify-center w-9 h-9 rounded-full bg-fg text-white hover:opacity-90" aria-label="Share on X">
                                <x-storefront.icon name="twitter" class="w-4 h-4" />
                            </a>
                            <button type="button" @click="share()" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-full border border-border text-navy-900 hover:border-navy-900">
                                <x-storefront.icon name="link" class="w-4 h-4" />
                                <span x-text="copied ? 'Link copied' : 'Copy link'">Copy link</span>
                            </button>
                        </div>

                        <ul class="mt-5 grid grid-cols-3 gap-2 text-center text-[11px] leading-tight text-muted">
                            <li class="flex flex-col items-center gap-1.5 rounded-sm bg-surface-muted px-2 py-3">
                                <x-storefront.icon name="lock" class="w-5 h-5 text-navy-900" />
                                Secure payment
                            </li>
                            <li class="flex flex-col items-center gap-1.5 rounded-sm bg-surface-muted px-2 py-3">
                                <x-storefront.icon name="shield-check" class="w-5 h-5 text-navy-900" />
                                Vetted vendor
                            </li>
                            <li class="flex flex-col items-center gap-1.5 rounded-sm bg-surface-muted px-2 py-3">
                                <x-storefront.icon name="arrow-path" class="w-5 h-5 text-navy-900" />
                                {{ config('group.returns.window_days') }}-day returns
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Delivery, returns, seller --}}
                <aside class="space-y-4 lg:sticky lg:top-32">
                    <div class="bg-surface border border-border rounded-lg">
                        <h2 class="flex items-center gap-2 px-4 h-11 border-b border-border font-display font-semibold text-sm text-navy-900">
                            <x-storefront.icon name="truck" class="w-4 h-4" />
                            Delivery &amp; returns
                        </h2>
                        <dl class="divide-y divide-border text-sm">
                            {{-- Timeframes come from config('group.delivery') — the same source as the Delivery Policy. --}}
                            @foreach (array_slice(config('group.delivery'), 0, 2) as $option)
                                <div class="px-4 py-3">
                                    <dt class="font-medium text-fg">{{ $option['service'] }}</dt>
                                    <dd class="text-muted tabular-nums">{{ $option['estimate'] }}</dd>
                                </div>
                            @endforeach
                            <div class="px-4 py-3">
                                <dt class="font-medium text-fg">Returns</dt>
                                <dd class="text-muted">{{ config('group.returns.window_days') }} days from delivery for faulty or wrong items.</dd>
                            </div>
                        </dl>
                        <div class="px-4 py-3 border-t border-border flex flex-wrap gap-x-4 gap-y-1 text-xs">
                            <a href="{{ route('storefront.delivery-policy') }}" class="font-semibold text-navy-500 hover:text-accent">Delivery policy</a>
                            <a href="{{ route('storefront.returns') }}" class="font-semibold text-navy-500 hover:text-accent">Returns policy</a>
                        </div>
                    </div>

                    @if (! empty($vendor['business_name']))
                        <div class="bg-surface border border-border rounded-lg p-4">
                            <p class="text-xs uppercase tracking-wide text-muted mb-2">Sold by</p>
                            <div class="flex items-center gap-3 mb-4">
                                <span class="flex items-center justify-center w-10 h-10 rounded-full bg-navy-900 text-white shrink-0">
                                    <x-storefront.icon name="store" class="w-5 h-5" />
                                </span>
                                <p class="font-semibold text-sm text-fg leading-snug">{{ $vendor['business_name'] }}</p>
                            </div>
                            <x-storefront.ui.button href="{{ route('storefront.vendor', $vendor['slug']) }}" variant="secondary" size="sm" class="w-full">
                                Visit store
                            </x-storefront.ui.button>
                        </div>
                    @endif

                    <div class="bg-surface border border-border rounded-lg p-4 flex items-start gap-3">
                        <x-storefront.icon name="phone" class="w-5 h-5 text-navy-500 shrink-0 mt-0.5" />
                        <div class="text-sm leading-snug">
                            <p class="font-semibold text-fg">Questions about this item?</p>
                            <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="text-navy-500 hover:text-accent tabular-nums">{{ config('group.contact.phone_gh') }}</a>
                        </div>
                    </div>
                </aside>
            </div>

            {{-- Description --}}
            @if (! empty($product['description']))
                <section class="mt-4 bg-surface border border-border rounded-lg">
                    <h2 class="px-4 sm:px-6 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">Product details</h2>
                    <div class="px-4 sm:px-6 py-5 text-sm text-muted leading-relaxed max-w-[75ch]">{!! nl2br(e($product['description'])) !!}</div>
                </section>
            @endif

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

            {{-- Reviews --}}
            <section id="reviews" class="mt-4 bg-surface border border-border rounded-lg scroll-mt-32">
                <h2 class="px-4 sm:px-6 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">
                    Customer reviews <span class="ml-2 text-sm font-sans font-normal text-muted tabular-nums">({{ count($reviews) }})</span>
                </h2>
                <div class="px-4 sm:px-6 py-2 grid lg:grid-cols-[minmax(0,1fr)_320px] gap-x-10">
                    <div>
                        @forelse ($reviews as $review)
                            <div class="border-b border-border last:border-b-0 py-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="flex text-warning" aria-label="{{ $review['rating'] }} out of 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <x-storefront.icon :name="$i <= $review['rating'] ? 'star' : 'star-outline'" class="w-3.5 h-3.5" />
                                        @endfor
                                    </span>
                                    <span class="font-semibold text-sm text-fg">{{ $review['user']['name'] ?? 'Customer' }}</span>
                                </div>
                                @if (! empty($review['title']))
                                    <p class="font-medium text-sm text-fg mb-1">{{ $review['title'] }}</p>
                                @endif
                                <p class="text-sm text-muted leading-relaxed">{{ $review['body'] }}</p>
                            </div>
                        @empty
                            <p class="text-muted text-sm py-4">No reviews yet — be the first to review this product.</p>
                        @endforelse
                    </div>

                    <div class="py-4 lg:border-l lg:border-border lg:pl-10">
                        @auth
                            <form
                                wire:submit.prevent="submitReview($refs.rating.value, $refs.title.value, $refs.body.value)"
                                x-data="{ submitting: false }"
                                @toast.window="submitting = false"
                                class="space-y-4"
                            >
                                <h3 class="font-semibold text-sm text-fg">Write a review</h3>
                                <div>
                                    <label for="review-rating" class="block text-sm font-medium text-fg mb-1.5">Rating</label>
                                    <select id="review-rating" x-ref="rating" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                                        <option value="5">5 stars</option>
                                        <option value="4">4 stars</option>
                                        <option value="3">3 stars</option>
                                        <option value="2">2 stars</option>
                                        <option value="1">1 star</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="review-title" class="block text-sm font-medium text-fg mb-1.5">Title (optional)</label>
                                    <input id="review-title" x-ref="title" type="text" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                                </div>
                                <div>
                                    <label for="review-body" class="block text-sm font-medium text-fg mb-1.5">Review</label>
                                    <textarea id="review-body" x-ref="body" rows="3" placeholder="Share your thoughts..." class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                                </div>
                                <x-storefront.ui.button type="submit" variant="primary" x-on:click="submitting = true" x-bind:disabled="submitting" class="w-full">
                                    <template x-if="submitting"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                                    Submit Review
                                </x-storefront.ui.button>
                            </form>
                        @else
                            <p class="text-sm text-muted"><a href="{{ route('storefront.login') }}" class="text-navy-500 hover:text-accent font-semibold">Sign in</a> to leave a review.</p>
                        @endauth
                    </div>
                </div>
            </section>

            {{-- Related products --}}
            @foreach ([
                ['title' => 'Similar items', 'icon' => 'tag', 'products' => $related['similar'] ?? [], 'href' => ! empty($product['category']['id']) ? route('storefront.category', $product['category']['id']) : route('storefront.shop')],
                ['title' => 'More from '.($vendor['business_name'] ?? 'this seller'), 'icon' => 'store', 'products' => $related['from_vendor'] ?? [], 'href' => ! empty($vendor['slug']) ? route('storefront.vendor', $vendor['slug']) : null],
            ] as $row)
                @if (count($row['products']))
                    <x-storefront.home-section :title="$row['title']" :icon="$row['icon']" :href="$row['href']" class="mt-4">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            @foreach (array_slice($row['products'], 0, 5) as $relatedProduct)
                                <x-storefront.product-card :product="$relatedProduct" />
                            @endforeach
                        </div>
                    </x-storefront.home-section>
                @endif
            @endforeach

            {{-- Recently viewed --}}
            <section
                wire:ignore
                x-data="{
                    items: [],
                    init() {
                        const all = JSON.parse(localStorage.getItem('recently_viewed_products') || '[]');
                        this.items = all.filter(p => p.id !== '{{ $product['id'] }}').slice(0, 5);
                    }
                }"
                x-show="items.length"
                x-cloak
                class="mt-4 bg-surface border border-border rounded-lg"
            >
                <h2 class="px-4 sm:px-6 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">Recently viewed</h2>
                <div class="p-3 sm:p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <template x-for="item in items" :key="item.id">
                        <a :href="'/product/' + item.id" class="group block border border-border rounded-lg overflow-hidden hover:border-navy-200">
                            <div class="aspect-square bg-surface-muted overflow-hidden">
                                <img :src="item.image || '{{ asset('images/product-placeholder.png') }}'" :alt="item.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" onerror="this.src='{{ asset('images/product-placeholder.png') }}'">
                            </div>
                            <div class="p-3">
                                <p x-text="item.name" class="text-sm text-fg line-clamp-2 mb-1 group-hover:text-accent"></p>
                                <p x-text="'GHS ' + Number(item.price_ghs).toFixed(2)" class="font-display font-bold text-navy-900 tabular-nums text-sm"></p>
                            </div>
                        </a>
                    </template>
                </div>
            </section>
        </div>
    </div>

    @php
        $productSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => \Illuminate\Support\Str::limit(strip_tags($product['description'] ?? ''), 300),
            'sku' => $product['sku'] ?? null,
            'image' => array_column($images, 'url') ?: null,
            'category' => $product['category']['name'] ?? null,
            'brand' => ! empty($product['specifications']['brand']) ? ['@type' => 'Brand', 'name' => $product['specifications']['brand']] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('storefront.product', $product['id']),
                'priceCurrency' => 'GHS',
                'price' => number_format($price, 2, '.', ''),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'seller' => ['@type' => 'Organization', 'name' => $vendor['business_name'] ?? config('app.name')],
            ],
            'aggregateRating' => $reviewsCount > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $product['average_rating'], 1),
                'reviewCount' => $reviewsCount,
            ] : null,
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
