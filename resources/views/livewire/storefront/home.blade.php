<div>
    {{-- Hero: real admin-managed banners when available, brand panel otherwise --}}
    @if ($banners->isNotEmpty())
        <section
            class="relative group"
            x-data="{
                active: 0,
                count: {{ $banners->count() }},
                timer: null,
                start() { if (this.count > 1) { this.timer = setInterval(() => this.next(), 5000) } },
                stop() { clearInterval(this.timer) },
                next() { this.active = (this.active + 1) % this.count },
                prev() { this.active = (this.active - 1 + this.count) % this.count },
            }"
            x-init="start()"
            @mouseenter="stop()"
            @mouseleave="start()"
        >
            <div class="relative h-90 md:h-115 overflow-hidden bg-navy-900">
                @foreach ($banners as $index => $banner)
                    <a
                        href="{{ $banner->link_url ?? '#' }}"
                        x-show="active === {{ $index }}"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="absolute inset-0"
                    >
                        <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-linear-to-t from-navy-950/70 via-navy-950/10 to-transparent"></div>
                        @if ($banner->title)
                            <div class="absolute inset-x-0 bottom-0 max-w-7xl mx-auto px-4 sm:px-6 pb-10">
                                <h1 class="font-display font-bold text-2xl md:text-4xl text-white max-w-xl">{{ $banner->title }}</h1>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <button
                    @click="prev()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center justify-center w-10 h-10 rounded-full bg-white/90 text-navy-900 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white"
                    aria-label="Previous slide"
                >
                    <x-storefront.icon name="chevron-left" class="w-5 h-5" />
                </button>
                <button
                    @click="next()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center justify-center w-10 h-10 rounded-full bg-white/90 text-navy-900 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white"
                    aria-label="Next slide"
                >
                    <x-storefront.icon name="chevron-right" class="w-5 h-5" />
                </button>

                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5">
                    @foreach ($banners as $index => $banner)
                        <button @click="active = {{ $index }}" class="w-6 h-1.5 rounded-full transition-colors" x-bind:class="active === {{ $index }} ? 'bg-accent' : 'bg-white/40'" aria-label="Show slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="bg-navy-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
                <div>
                    <p class="text-accent font-semibold text-sm uppercase tracking-wide mb-3">{{ config('app.name') }} Marketplace</p>
                    <h1 class="font-display font-bold text-3xl md:text-5xl leading-tight mb-5 text-balance">
                        Shop local vendors, delivered with confidence.
                    </h1>
                    <p class="text-white/70 text-base md:text-lg mb-8 max-w-md">
                        Backed by the {{ config('group.parent.name') }}' logistics network — thousands of products from vetted vendors across Ghana.
                    </p>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="accent" size="lg">
                        Start Shopping
                        <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                    </x-storefront.ui.button>
                </div>
                <div class="hidden md:flex justify-center">
                    <img src="{{ asset('images/brand/logo-stacked-on-dark.png') }}" alt="" class="w-64 opacity-90">
                </div>
            </div>
        </section>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        {{-- Trust strip --}}
        <section class="grid grid-cols-2 md:grid-cols-4 gap-4 -mt-8 md:-mt-10 relative z-10 mb-12">
            @foreach ([
                ['icon' => 'truck', 'title' => 'Reliable Delivery', 'desc' => 'Backed by our logistics network'],
                ['icon' => 'shield-check', 'title' => 'Secure Payment', 'desc' => 'Paystack & card protected'],
                ['icon' => 'box', 'title' => 'Verified Vendors', 'desc' => 'Every seller is vetted'],
                ['icon' => 'clock', 'title' => '24/7 Support', 'desc' => 'We\'re here when you need us'],
            ] as $item)
                <div class="flex items-start gap-3 bg-surface border border-border rounded-lg p-4">
                    <span class="flex items-center justify-center w-10 h-10 rounded-sm bg-navy-900/5 text-navy-900 shrink-0">
                        <x-storefront.icon :name="$item['icon']" class="w-5 h-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-sm text-fg">{{ $item['title'] }}</p>
                        <p class="text-xs text-muted">{{ $item['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-12">{{ $error }}</x-storefront.ui.alert>
        @endif

        @if (count($categories))
            <section class="mb-16">
                <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline mb-8">Shop by Category</h2>
                <div class="flex md:grid md:grid-cols-6 gap-4 overflow-x-auto pb-2 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 snap-x snap-mandatory">
                    @foreach ($categories as $category)
                        <a href="{{ route('storefront.category', $category['id']) }}" class="group shrink-0 w-28 md:w-auto snap-start flex flex-col items-center text-center">
                            <span class="flex items-center justify-center w-20 h-20 rounded-full bg-surface-muted overflow-hidden mb-3 group-hover:ring-2 group-hover:ring-accent transition-shadow">
                                <img src="{{ $category['image_url'] ?? asset('images/product-placeholder.png') }}" alt="{{ $category['name'] }}" class="w-full h-full object-cover" loading="lazy">
                            </span>
                            <span class="text-sm font-medium text-fg group-hover:text-accent">{{ $category['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($dealToday))
            <section class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline">Deal of the Day</h2>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm">View all <x-storefront.icon name="arrow-right" class="w-4 h-4" /></x-storefront.ui.button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                    @foreach ($dealToday as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($featured))
            <section class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline">Featured Products</h2>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm">View all <x-storefront.icon name="arrow-right" class="w-4 h-4" /></x-storefront.ui.button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                    @foreach ($featured as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($newArrivals))
            <section class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline">New Arrivals</h2>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm">View all <x-storefront.icon name="arrow-right" class="w-4 h-4" /></x-storefront.ui.button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                    @foreach ($newArrivals as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($bestSellers))
            <section class="mb-16">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="font-display font-semibold text-2xl text-navy-900 wake-underline">Best Sellers</h2>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm">View all <x-storefront.icon name="arrow-right" class="w-4 h-4" /></x-storefront.ui.button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                    @foreach ($bestSellers as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (! count($categories) && ! count($featured) && ! count($newArrivals) && ! count($bestSellers) && ! $error)
            <x-storefront.ui.empty-state icon="box" title="No products yet" description="Check back soon — new vendors and products are added regularly." class="mb-16" />
        @endif
    </div>

    {{-- Become a vendor CTA --}}
    <section class="bg-navy-900 text-white mt-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
            <div>
                <h2 class="font-display font-semibold text-2xl mb-2">Sell on {{ config('app.name') }}</h2>
                <p class="text-white/70 max-w-md">Reach thousands of shoppers and let our logistics network handle delivery.</p>
            </div>
            <x-storefront.ui.button href="{{ route('storefront.become-vendor') }}" variant="accent" size="lg">Become a Vendor</x-storefront.ui.button>
        </div>
    </section>
</div>
