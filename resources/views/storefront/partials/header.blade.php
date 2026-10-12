{{-- Storefront header: utility bar, sticky logo/search/actions bar, category nav, mobile drawer. --}}
@php
    $headerCategories = $headerCategories ?? [];

    $mobileLinks = [
        ['label' => 'Home', 'route' => 'storefront.home', 'icon' => 'grid'],
        ['label' => 'Shop all products', 'route' => 'storefront.shop', 'icon' => 'tag'],
        ['label' => 'Vendors', 'route' => 'storefront.vendors', 'icon' => 'store'],
        ['label' => 'Track Order', 'route' => 'storefront.track-order', 'icon' => 'truck'],
        ['label' => 'Wishlist', 'route' => 'storefront.wishlist', 'icon' => 'heart'],
        ['label' => 'Cart', 'route' => 'storefront.cart', 'icon' => 'cart'],
        ['label' => 'Sell on '.config('app.name'), 'route' => 'storefront.become-vendor', 'icon' => 'building'],
        ['label' => 'Help & FAQs', 'route' => 'storefront.faq', 'icon' => 'question'],
        ['label' => 'Contact Us', 'route' => 'storefront.contact', 'icon' => 'phone'],
        ['label' => 'About Us', 'route' => 'storefront.about', 'icon' => 'shield-check'],
        ['label' => 'Our Group', 'route' => 'storefront.group', 'icon' => 'building'],
    ];
@endphp

{{-- Utility bar --}}
<div class="bg-navy-950 text-white/75 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-8 flex items-center justify-between gap-4">
        <p class="flex items-center gap-1.5 truncate">
            <x-storefront.icon name="truck" class="w-3.5 h-3.5 text-accent shrink-0" />
            <span class="truncate">Delivery to every region of Ghana · Pay securely with Mobile Money or card</span>
        </p>
        <nav class="hidden md:flex items-center gap-5 shrink-0" aria-label="Utility">
            <a href="{{ route('storefront.become-vendor') }}" class="hover:text-white">Sell on {{ config('app.name') }}</a>
            <a href="{{ route('storefront.track-order') }}" class="hover:text-white">Track Order</a>
            <a href="{{ route('storefront.faq') }}" class="hover:text-white">Help</a>
            <a href="tel:{{ config('group.contact.phone_gh_tel') }}" class="hover:text-white tabular-nums">{{ config('group.contact.phone_gh') }}</a>
        </nav>
    </div>
</div>

<header
    x-data="{ categoriesOpen: false, mobileOpen: false, accountOpen: false }"
    @keydown.escape.window="categoriesOpen = false; mobileOpen = false; accountOpen = false"
    class="sticky top-0 z-30 bg-surface"
>
    {{-- Logo / search / actions --}}
    <div class="border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 lg:py-0 lg:h-18 flex flex-wrap lg:flex-nowrap items-center gap-x-3 gap-y-3 lg:gap-6">
            <button @click="mobileOpen = true" class="lg:hidden -ml-2 p-2 text-navy-900" aria-label="Open menu">
                <x-storefront.icon name="menu" class="w-6 h-6" />
            </button>

            <a href="{{ route('storefront.home') }}" class="flex items-center shrink-0" aria-label="{{ config('app.name') }} home">
                {{-- Mark alone below sm:, full lockup above — the wordmark is unreadable at phone widths. --}}
                <img src="{{ asset('images/brand/logo-mark.png') }}" alt="{{ config('app.name') }}" class="sm:hidden h-8 w-auto" width="512" height="431">
                <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="hidden sm:block h-9 lg:h-10 w-auto" width="1200" height="375">
            </a>

            <form method="get" action="{{ route('storefront.shop') }}" role="search" class="order-last lg:order-none basis-full lg:basis-auto flex-1 lg:max-w-3xl">
                <div class="flex w-full h-11 rounded-sm border-2 border-navy-900 overflow-hidden focus-within:border-accent transition-colors">
                    <label for="header-search-category" class="sr-only">Category</label>
                    <select
                        id="header-search-category"
                        name="category_id"
                        class="hidden md:block max-w-44 border-r border-border bg-surface-muted text-sm text-fg px-3 focus:outline-none"
                    >
                        <option value="">All Categories</option>
                        @foreach ($headerCategories as $category)
                            <option value="{{ $category['id'] }}" @selected(request('category_id') == $category['id'])>{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    <label for="header-search" class="sr-only">Search</label>
                    <input
                        id="header-search"
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search products, brands and vendors"
                        class="flex-1 min-w-0 px-4 text-sm bg-surface placeholder:text-muted focus:outline-none"
                    >
                    <button type="submit" class="flex items-center gap-2 px-4 sm:px-6 bg-navy-900 text-white text-sm font-semibold hover:bg-accent transition-colors" aria-label="Search">
                        <x-storefront.icon name="search" class="w-5 h-5" />
                        <span class="hidden sm:inline">Search</span>
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-1 sm:gap-2 ml-auto lg:ml-0 shrink-0">
                {{-- Account --}}
                <div class="relative" @click.outside="accountOpen = false">
                    <button
                        @click="accountOpen = !accountOpen"
                        :aria-expanded="accountOpen"
                        class="flex items-center gap-2 p-2 rounded-sm text-navy-900 hover:bg-surface-muted"
                    >
                        <x-storefront.icon name="user" class="w-6 h-6" />
                        <span class="hidden xl:flex flex-col items-start leading-tight text-left">
                            <span class="text-[11px] text-muted">
                                @auth Hello, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} @else Hello, sign in @endauth
                            </span>
                            <span class="text-sm font-semibold">Account</span>
                        </span>
                        <x-storefront.icon name="chevron-down" class="hidden xl:block w-3.5 h-3.5 text-muted" />
                    </button>
                    <div
                        x-show="accountOpen"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-cloak
                        class="absolute right-0 top-full mt-2 w-60 bg-surface border border-border rounded-lg shadow-float p-2 z-40"
                    >
                        @guest
                            <div class="p-2 pb-3 border-b border-border mb-1 flex gap-2">
                                <x-storefront.ui.button href="{{ route('storefront.login') }}" variant="primary" size="sm" class="flex-1">Sign In</x-storefront.ui.button>
                                <x-storefront.ui.button href="{{ route('storefront.register') }}" variant="secondary" size="sm" class="flex-1">Register</x-storefront.ui.button>
                            </div>
                        @endguest
                        @foreach ([
                            ['label' => 'My Account', 'route' => 'storefront.account.dashboard', 'icon' => 'user'],
                            ['label' => 'Orders', 'route' => 'storefront.account.orders', 'icon' => 'box'],
                            ['label' => 'Wishlist', 'route' => 'storefront.wishlist', 'icon' => 'heart'],
                            ['label' => 'Track Order', 'route' => 'storefront.track-order', 'icon' => 'truck'],
                        ] as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2 rounded-sm text-sm text-fg hover:bg-surface-muted">
                                <x-storefront.icon :name="$item['icon']" class="w-4 h-4 text-muted" />
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('storefront.wishlist') }}" class="hidden sm:flex items-center gap-2 p-2 rounded-sm text-navy-900 hover:bg-surface-muted" aria-label="Wishlist">
                    <x-storefront.icon name="heart" class="w-6 h-6" />
                    <span class="hidden xl:block text-sm font-semibold">Wishlist</span>
                </a>

                @livewire('storefront.header.cart-icon')
            </div>
        </div>
    </div>

    {{-- Category nav bar --}}
    <div class="hidden lg:block bg-navy-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-11 flex items-center gap-6">
            <div class="relative h-full" @click.outside="categoriesOpen = false">
                <button
                    @click="categoriesOpen = !categoriesOpen"
                    :aria-expanded="categoriesOpen"
                    class="flex items-center gap-2 h-full px-4 bg-accent font-semibold text-sm hover:bg-accent-hover"
                >
                    <x-storefront.icon name="menu" class="w-4 h-4" />
                    All Categories
                    <x-storefront.icon name="chevron-down" class="w-3.5 h-3.5" />
                </button>
                <div
                    x-show="categoriesOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="absolute left-0 top-full w-72 bg-surface text-fg border border-border rounded-b-lg shadow-float py-2 max-h-[70vh] overflow-y-auto"
                >
                    @forelse ($headerCategories as $category)
                        <a href="{{ route('storefront.category', $category['id']) }}" class="flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-surface-muted hover:text-accent">
                            {{ $category['name'] }}
                            <x-storefront.icon name="chevron-right" class="w-3.5 h-3.5 text-muted" />
                        </a>
                    @empty
                        <a href="{{ route('storefront.shop') }}" class="block px-4 py-2 text-sm hover:bg-surface-muted">Browse all products</a>
                    @endforelse
                </div>
            </div>

            <nav class="flex-1 min-w-0" aria-label="Categories">
                <ul class="flex items-center gap-5 text-sm overflow-hidden">
                    <li class="shrink-0">
                        <a href="{{ route('storefront.shop') }}" class="font-semibold {{ request()->routeIs('storefront.shop') ? 'text-white' : 'text-white/80 hover:text-white' }}">Shop All</a>
                    </li>
                    @foreach (array_slice($headerCategories, 0, 7) as $category)
                        <li class="shrink-0">
                            <a href="{{ route('storefront.category', $category['id']) }}" class="text-white/80 hover:text-white whitespace-nowrap">{{ $category['name'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-5 text-sm shrink-0">
                <a href="{{ route('storefront.vendors') }}" class="flex items-center gap-1.5 {{ request()->routeIs('storefront.vendors') ? 'text-white' : 'text-white/80 hover:text-white' }}">
                    <x-storefront.icon name="store" class="w-4 h-4" />
                    Vendors
                </a>
                <a href="{{ route('storefront.become-vendor') }}" class="font-semibold text-white hover:text-accent">Sell on {{ config('app.name') }}</a>
            </div>
        </div>
    </div>

    {{-- Mobile menu drawer --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-out duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileOpen = false"
            class="absolute inset-0 bg-navy-950/60"
        ></div>
        <div
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-out duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative w-80 max-w-[85%] h-full bg-surface flex flex-col"
        >
            <div class="flex items-center justify-between px-4 h-14 bg-navy-900 text-white">
                <span class="flex items-center gap-2 text-sm font-semibold">
                    <x-storefront.icon name="user" class="w-5 h-5" />
                    @auth Hello, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} @else Welcome @endauth
                </span>
                <button @click="mobileOpen = false" class="p-2 -mr-2" aria-label="Close menu">
                    <x-storefront.icon name="x-mark" class="w-6 h-6" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto">
                <ul class="py-2 text-sm border-b border-border">
                    @foreach ($mobileLinks as $link)
                        <li>
                            <a href="{{ route($link['route']) }}" class="flex items-center gap-3 px-4 py-3 {{ request()->routeIs($link['route']) ? 'text-accent font-semibold' : 'text-fg' }}">
                                <x-storefront.icon :name="$link['icon']" class="w-5 h-5 text-muted" />
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if (count($headerCategories))
                    <p class="px-4 pt-4 pb-2 text-xs font-semibold uppercase tracking-wide text-muted">Categories</p>
                    <ul class="pb-2 text-sm">
                        @foreach ($headerCategories as $category)
                            <li>
                                <a href="{{ route('storefront.category', $category['id']) }}" class="flex items-center justify-between px-4 py-2.5 text-fg">
                                    {{ $category['name'] }}
                                    <x-storefront.icon name="chevron-right" class="w-4 h-4 text-muted" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </nav>

            <div class="px-4 py-4 border-t border-border">
                @auth
                    <a href="{{ route('storefront.account.dashboard') }}" class="block text-center rounded-sm bg-navy-900 text-white py-2.5 text-sm font-semibold">My Account</a>
                @else
                    <div class="flex gap-2">
                        <a href="{{ route('storefront.login') }}" class="flex-1 text-center rounded-sm border border-navy-900 text-navy-900 py-2.5 text-sm font-semibold">Sign In</a>
                        <a href="{{ route('storefront.register') }}" class="flex-1 text-center rounded-sm bg-navy-900 text-white py-2.5 text-sm font-semibold">Register</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
