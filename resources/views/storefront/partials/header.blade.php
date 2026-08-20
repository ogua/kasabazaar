{{-- Storefront header: utility bar, logo/search/cart, category + nav bar. --}}
<header x-data="{ categoriesOpen: false, mobileOpen: false, accountOpen: false }" class="relative z-30">
    {{-- Utility bar --}}
    <div class="bg-navy-950 text-white/80 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-9 flex items-center justify-between">
            <p class="hidden sm:block">Welcome to {{ config('app.name') }} — shop from vetted vendors across Ghana.</p>
            <div class="flex items-center gap-4 ml-auto">
                <a href="{{ config('group.parent.companies_url') }}" target="_blank" rel="noopener" class="hidden md:inline hover:text-white">{{ config('group.parent.name') }}</a>
                <a href="{{ route('storefront.contact') }}" class="hover:text-white">Contact Us</a>
                @auth
                    <a href="{{ route('storefront.account.dashboard') }}" class="hover:text-white">My Account</a>
                @else
                    <a href="{{ route('storefront.login') }}" class="hover:text-white">Sign In</a>
                    <a href="{{ route('storefront.register') }}" class="hover:text-white">Register</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Logo / search / actions --}}
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center gap-4">
            <button @click="mobileOpen = true" class="lg:hidden -ml-1 p-2 text-navy-900" aria-label="Open menu">
                <x-storefront.icon name="menu" class="w-6 h-6" />
            </button>

            <a href="{{ route('storefront.home') }}" class="flex items-center shrink-0" aria-label="{{ config('app.name') }} home">
                {{-- Mark alone below sm:, full lockup above — the wordmark is unreadable at phone widths. --}}
                <img src="{{ asset('images/brand/logo-mark.png') }}" alt="{{ config('app.name') }}" class="sm:hidden h-9 w-auto" width="512" height="431">
                <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="hidden sm:block h-10 w-auto" width="1200" height="375">
            </a>

            <form method="get" action="{{ route('storefront.shop') }}" class="hidden md:flex flex-1 max-w-2xl mx-auto">
                <div class="flex w-full">
                    <select name="category_id" class="hidden lg:block border border-r-0 border-border rounded-l-sm bg-surface-muted text-sm px-3 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        <option value="">All Categories</option>
                        @foreach ($headerCategories ?? [] as $category)
                            <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    <input
                        type="text"
                        name="search"
                        placeholder="Search products, vendors..."
                        class="flex-1 border border-border lg:border-l-0 px-4 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                    >
                    <button type="submit" class="flex items-center justify-center px-4 bg-navy-900 text-white rounded-r-sm hover:bg-navy-700" aria-label="Search">
                        <x-storefront.icon name="search" class="w-5 h-5" />
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-1 ml-auto">
                <a href="{{ route('storefront.shop') }}" class="md:hidden p-2 text-navy-900" aria-label="Search">
                    <x-storefront.icon name="search" class="w-5 h-5" />
                </a>
                <a href="{{ route('storefront.wishlist') }}" class="hidden sm:flex items-center gap-1.5 p-2 text-navy-900 hover:text-accent" aria-label="Wishlist">
                    <x-storefront.icon name="heart" class="w-5 h-5" />
                </a>
                @livewire('storefront.header.cart-icon')
            </div>
        </div>
    </div>

    {{-- Category + nav bar --}}
    <div class="hidden lg:block bg-navy-900 text-white sticky top-0 z-30 shadow-float">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-12 flex items-center gap-8">
            <div class="relative" @click.outside="categoriesOpen = false">
                <button @click="categoriesOpen = !categoriesOpen" class="flex items-center gap-2 font-semibold text-sm h-12 px-1">
                    <x-storefront.icon name="grid" class="w-4 h-4" />
                    Browse Categories
                    <x-storefront.icon name="chevron-down" class="w-3.5 h-3.5" />
                </button>
                <div
                    x-show="categoriesOpen"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-cloak
                    class="absolute left-0 top-full w-72 bg-white text-fg border border-border rounded-b-lg shadow-float py-2 max-h-96 overflow-y-auto"
                >
                    @forelse ($headerCategories ?? [] as $category)
                        <a href="{{ route('storefront.category', $category['id']) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-surface-muted">
                            {{ $category['name'] }}
                        </a>
                    @empty
                        <a href="{{ route('storefront.shop') }}" class="block px-4 py-2 text-sm hover:bg-surface-muted">Browse all products</a>
                    @endforelse
                </div>
            </div>

            <nav>
                <ul class="flex items-center gap-6 text-sm font-medium">
                    @php
                        $navLinks = [
                            ['label' => 'Home', 'route' => 'storefront.home'],
                            ['label' => 'Shop', 'route' => 'storefront.shop'],
                            ['label' => 'Vendors', 'route' => 'storefront.vendors'],
                            ['label' => 'Sell on '.config('app.name'), 'route' => 'storefront.become-vendor'],
                            ['label' => 'About Us', 'route' => 'storefront.about'],
                            ['label' => 'Our Group', 'route' => 'storefront.group'],
                        ];
                    @endphp
                    @foreach ($navLinks as $link)
                        <li>
                            <a
                                href="{{ route($link['route']) }}"
                                class="inline-flex items-center h-12 border-b-2 {{ request()->routeIs($link['route']) ? 'border-accent text-white' : 'border-transparent text-white/75 hover:text-white' }}"
                            >
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <a href="{{ route('storefront.track-order') }}" class="ml-auto flex items-center gap-1.5 text-sm text-white/75 hover:text-white">
                <x-storefront.icon name="truck" class="w-4 h-4" />
                Track Order
            </a>
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
            class="relative w-80 max-w-[85%] h-full bg-white flex flex-col"
        >
            <div class="flex items-center justify-between px-4 h-16 border-b border-border">
                <img src="{{ asset('images/brand/logo-lockup.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
                <button @click="mobileOpen = false" class="p-2 text-navy-900" aria-label="Close menu">
                    <x-storefront.icon name="x-mark" class="w-6 h-6" />
                </button>
            </div>

            <form method="get" action="{{ route('storefront.shop') }}" class="flex px-4 py-3 border-b border-border">
                <input type="text" name="search" placeholder="Search products..." class="flex-1 border border-border rounded-l-sm px-3 py-2 text-sm focus:outline-none">
                <button type="submit" class="px-3 bg-navy-900 text-white rounded-r-sm" aria-label="Search"><x-storefront.icon name="search" class="w-4 h-4" /></button>
            </form>

            <nav class="flex-1 overflow-y-auto py-2">
                <ul class="text-sm">
                    @foreach ([
                        ['label' => 'Home', 'route' => 'storefront.home'],
                        ['label' => 'Shop', 'route' => 'storefront.shop'],
                        ['label' => 'Vendors', 'route' => 'storefront.vendors'],
                        ['label' => 'Sell on '.config('app.name'), 'route' => 'storefront.become-vendor'],
                        ['label' => 'Wishlist', 'route' => 'storefront.wishlist'],
                        ['label' => 'Cart', 'route' => 'storefront.cart'],
                        ['label' => 'About Us', 'route' => 'storefront.about'],
                        ['label' => 'Our Group', 'route' => 'storefront.group'],
                        ['label' => 'Contact Us', 'route' => 'storefront.contact'],
                        ['label' => 'FAQs', 'route' => 'storefront.faq'],
                    ] as $link)
                        <li>
                            <a href="{{ route($link['route']) }}" class="block px-4 py-3 border-b border-border {{ request()->routeIs($link['route']) ? 'text-accent font-semibold' : 'text-fg' }}">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
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
