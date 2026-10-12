<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-4 space-y-4">
        {{-- Above the fold: category rail, hero, quick actions --}}
        <section class="grid gap-4 lg:grid-cols-[232px_minmax(0,1fr)] xl:grid-cols-[232px_minmax(0,1fr)_248px] lg:h-100">
            {{-- Category rail (desktop) --}}
            <nav class="hidden lg:flex flex-col bg-surface border border-border rounded-lg overflow-hidden" aria-label="Shop by category">
                <p class="flex items-center gap-2 px-4 h-11 shrink-0 border-b border-border font-display font-semibold text-sm text-navy-900">
                    <x-storefront.icon name="grid" class="w-4 h-4" />
                    Categories
                </p>
                <ul class="flex-1 overflow-y-auto py-1 text-sm">
                    @forelse (array_slice($categories, 0, 11) as $category)
                        <li>
                            <a href="{{ route('storefront.category', $category['id']) }}" class="group flex items-center justify-between gap-2 px-4 py-2 text-fg hover:bg-surface-muted hover:text-accent">
                                <span class="truncate">{{ $category['name'] }}</span>
                                <x-storefront.icon name="chevron-right" class="w-3.5 h-3.5 text-muted group-hover:text-accent shrink-0" />
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-2 text-muted">Categories will appear here soon.</li>
                    @endforelse
                </ul>
                <a href="{{ route('storefront.shop') }}" class="flex items-center justify-between px-4 h-10 shrink-0 border-t border-border text-sm font-semibold text-navy-500 hover:text-accent">
                    Shop all products
                    <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                </a>
            </nav>

            {{-- Hero: real admin-managed banners when available, brand panel otherwise --}}
            @if ($banners->isNotEmpty())
                <div
                    class="relative group h-52 sm:h-72 lg:h-full rounded-lg overflow-hidden bg-navy-900"
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
                    @foreach ($banners as $index => $banner)
                        <a
                            href="{{ $banner->link_url ?? '#' }}"
                            x-show="active === {{ $index }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="absolute inset-0"
                        >
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="w-full h-full object-cover" @if ($index > 0) loading="lazy" @endif>
                            @if ($banner->title)
                                <div class="absolute inset-0 bg-linear-to-t from-navy-950/70 via-navy-950/10 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-8">
                                    <h1 class="font-display font-bold text-xl sm:text-3xl text-white max-w-lg text-balance">{{ $banner->title }}</h1>
                                </div>
                            @endif
                        </a>
                    @endforeach

                    @if ($banners->count() > 1)
                        <button
                            @click="prev()"
                            class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center justify-center w-9 h-9 rounded-full bg-white/90 text-navy-900 opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition-opacity hover:bg-white"
                            aria-label="Previous slide"
                        >
                            <x-storefront.icon name="chevron-left" class="w-5 h-5" />
                        </button>
                        <button
                            @click="next()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center w-9 h-9 rounded-full bg-white/90 text-navy-900 opacity-0 group-hover:opacity-100 focus-visible:opacity-100 transition-opacity hover:bg-white"
                            aria-label="Next slide"
                        >
                            <x-storefront.icon name="chevron-right" class="w-5 h-5" />
                        </button>

                        <div class="absolute bottom-3 right-4 flex gap-1.5">
                            @foreach ($banners as $index => $banner)
                                <button @click="active = {{ $index }}" class="w-5 h-1.5 rounded-full transition-colors" x-bind:class="active === {{ $index }} ? 'bg-accent' : 'bg-white/50'" aria-label="Show slide {{ $index + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="relative h-auto lg:h-full rounded-lg overflow-hidden bg-navy-900 text-white">
                    <div class="relative h-full grid sm:grid-cols-[1fr_auto] items-center gap-6 p-6 sm:p-10">
                        <div>
                            <p class="text-accent font-semibold text-xs uppercase tracking-wide mb-3">{{ config('app.name') }} Marketplace</p>
                            <h1 class="font-display font-bold text-2xl sm:text-4xl leading-tight mb-4 text-balance max-w-md">
                                Shop local vendors, delivered with confidence.
                            </h1>
                            <p class="text-white/70 text-sm sm:text-base mb-6 max-w-md">
                                Thousands of products from vetted vendors across Ghana, delivered through the
                                {{ config('group.parent.name') }} logistics network.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="accent" size="lg">
                                    Start Shopping
                                    <x-storefront.icon name="arrow-right" class="w-4 h-4" />
                                </x-storefront.ui.button>
                                <x-storefront.ui.button href="{{ route('storefront.vendors') }}" size="lg" class="bg-white/10! hover:bg-white/20!">
                                    Browse Vendors
                                </x-storefront.ui.button>
                            </div>
                        </div>
                        <img src="{{ asset('images/brand/logo-mark-on-dark.png') }}" alt="" class="hidden sm:block w-40 lg:w-48 opacity-90">
                    </div>
                </div>
            @endif

            {{-- Quick actions (wide desktop) --}}
            <aside class="hidden xl:flex flex-col gap-4">
                <div class="bg-surface border border-border rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-muted text-navy-900">
                            <x-storefront.icon name="user" class="w-5 h-5" />
                        </span>
                        <div class="leading-tight">
                            @auth
                                <p class="text-xs text-muted">Welcome back,</p>
                                <p class="text-sm font-semibold text-fg truncate">{{ auth()->user()->name }}</p>
                            @else
                                <p class="text-sm font-semibold text-fg">Welcome to {{ config('app.name') }}</p>
                                <p class="text-xs text-muted">Sign in for orders &amp; wishlist</p>
                            @endauth
                        </div>
                    </div>
                    @auth
                        <div class="grid grid-cols-2 gap-2">
                            <x-storefront.ui.button href="{{ route('storefront.account.orders') }}" variant="primary" size="sm">My Orders</x-storefront.ui.button>
                            <x-storefront.ui.button href="{{ route('storefront.wishlist') }}" variant="secondary" size="sm">Wishlist</x-storefront.ui.button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-2">
                            <x-storefront.ui.button href="{{ route('storefront.login') }}" variant="primary" size="sm">Sign In</x-storefront.ui.button>
                            <x-storefront.ui.button href="{{ route('storefront.register') }}" variant="secondary" size="sm">Register</x-storefront.ui.button>
                        </div>
                    @endauth
                </div>

                <a href="{{ route('storefront.become-vendor') }}" class="group flex-1 flex flex-col justify-between bg-accent text-white rounded-lg p-4 hover:bg-accent-hover transition-colors">
                    <x-storefront.icon name="store" class="w-7 h-7" />
                    <div>
                        <p class="font-display font-semibold text-lg leading-snug mb-1">Sell on {{ config('app.name') }}</p>
                        <p class="text-xs text-white/85 mb-3">Reach shoppers nationwide — we handle payments and delivery.</p>
                        <span class="inline-flex items-center gap-1 text-sm font-semibold">
                            Become a vendor
                            <x-storefront.icon name="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                        </span>
                    </div>
                </a>

                <a href="{{ route('storefront.track-order') }}" class="flex items-center gap-3 bg-surface border border-border rounded-lg p-4 hover:border-navy-200">
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-navy-900 text-white shrink-0">
                        <x-storefront.icon name="truck" class="w-5 h-5" />
                    </span>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold text-fg">Track your order</span>
                        <span class="block text-xs text-muted">Live status, no sign-in needed</span>
                    </span>
                </a>
            </aside>
        </section>

        {{-- Trust strip --}}
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-border border border-border rounded-lg overflow-hidden">
            @foreach ([
                ['icon' => 'truck', 'title' => 'Nationwide delivery', 'desc' => 'To every region of Ghana'],
                ['icon' => 'lock', 'title' => 'Secure payment', 'desc' => 'Mobile Money & card via Paystack'],
                ['icon' => 'shield-check', 'title' => 'Vetted vendors', 'desc' => 'Every seller is reviewed'],
                ['icon' => 'arrow-path', 'title' => config('group.returns.window_days').'-day returns', 'desc' => 'On faulty or wrong items'],
            ] as $item)
                <div class="flex items-center gap-3 px-4 py-3.5 bg-surface">
                    <x-storefront.icon :name="$item['icon']" class="w-7 h-7 text-accent shrink-0" />
                    <div class="leading-tight min-w-0">
                        <p class="font-semibold text-sm text-fg">{{ $item['title'] }}</p>
                        <p class="text-xs text-muted truncate">{{ $item['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        @if ($error)
            <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
        @endif

        @if (count($categories))
            <x-storefront.home-section title="Shop by Category" icon="grid" :href="route('storefront.shop')">
                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-x-2 gap-y-4">
                    @foreach (array_slice($categories, 0, 16) as $category)
                        <a href="{{ route('storefront.category', $category['id']) }}" class="group flex flex-col items-center text-center gap-2">
                            <span class="block w-full aspect-square max-w-24 rounded-lg bg-surface-muted overflow-hidden ring-1 ring-border group-hover:ring-2 group-hover:ring-accent transition-shadow">
                                <img src="{{ $category['image_url'] ?? asset('images/product-placeholder.png') }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            </span>
                            <span class="text-xs sm:text-sm font-medium text-fg group-hover:text-accent line-clamp-2 leading-snug">{{ $category['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </x-storefront.home-section>
        @endif

        @foreach ([
            ['title' => 'Deal of the Day', 'icon' => 'bolt', 'tone' => 'deal', 'products' => $dealToday],
            ['title' => 'Featured Products', 'icon' => 'star', 'tone' => 'default', 'products' => $featured],
            ['title' => 'New Arrivals', 'icon' => 'tag', 'tone' => 'default', 'products' => $newArrivals],
            ['title' => 'Best Sellers', 'icon' => 'shield-check', 'tone' => 'default', 'products' => $bestSellers],
        ] as $row)
            @if (count($row['products']))
                <x-storefront.home-section :title="$row['title']" :icon="$row['icon']" :tone="$row['tone']" :href="route('storefront.shop')">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach ($row['products'] as $product)
                            <x-storefront.product-card :product="$product" />
                        @endforeach
                    </div>
                </x-storefront.home-section>
            @endif
        @endforeach

        @if (! count($categories) && ! count($featured) && ! count($newArrivals) && ! count($bestSellers) && ! $error)
            <x-storefront.ui.empty-state icon="box" title="No products yet" description="Check back soon — new vendors and products are added regularly." class="bg-surface" />
        @endif

        {{-- Become a vendor CTA --}}
        <section class="rounded-lg bg-navy-900 text-white overflow-hidden">
            <div class="grid md:grid-cols-[1fr_auto] items-center gap-6 px-6 sm:px-10 py-8">
                <div>
                    <p class="text-accent font-semibold text-xs uppercase tracking-wide mb-2">For businesses</p>
                    <h2 class="font-display font-semibold text-2xl mb-2">Grow your business on {{ config('app.name') }}</h2>
                    <p class="text-white/70 text-sm max-w-xl">List your products, get paid securely and let our logistics network carry your orders to shoppers in every region.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-storefront.ui.button href="{{ route('storefront.become-vendor') }}" variant="accent" size="lg">Become a Vendor</x-storefront.ui.button>
                    <x-storefront.ui.button href="{{ route('filament.vendor.auth.login') }}" size="lg" class="bg-white/10! hover:bg-white/20!">Vendor Sign In</x-storefront.ui.button>
                </div>
            </div>
        </section>
    </div>
</div>
