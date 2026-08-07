<header class="header">
    <div class="header-top">
        <div class="container">
            <div class="header-left">
                <p class="welcome-msg">Welcome to {{ config('app.name') }}!</p>
            </div>
            <div class="header-right pr-0">
                <span class="divider d-lg-show"></span>
                <a href="{{ route('storefront.blog') }}" class="d-lg-show">Blog</a>
                <a href="{{ route('storefront.contact') }}" class="d-lg-show">Contact Us</a>
                @auth
                    <a href="{{ route('storefront.account.dashboard') }}" class="d-lg-show">My Account</a>
                    <span class="delimiter d-lg-show">/</span>
                    <form method="POST" action="{{ route('storefront.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 ml-0 d-lg-show login" style="border:0;background:none;">Sign Out</button>
                    </form>
                @else
                    <a href="{{ route('storefront.login') }}" class="d-lg-show login sign-in"><i class="w-icon-account"></i>Sign In</a>
                    <span class="delimiter d-lg-show">/</span>
                    <a href="{{ route('storefront.register') }}" class="ml-0 d-lg-show login register">Register</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="header-middle">
        <div class="container">
            <div class="header-left mr-md-4">
                <a href="#" class="mobile-menu-toggle w-icon-hamburger" aria-label="menu-toggle"></a>
                <a href="{{ route('storefront.home') }}" class="logo ml-lg-0">
                    <img src="{{ asset('storefront/assets/images/demos/demo2/header-logo.png') }}" alt="{{ config('app.name') }}" width="144" height="45" />
                </a>
                <nav class="main-nav">
                    <ul class="menu">
                        <li class="{{ request()->routeIs('storefront.home') ? 'active' : '' }}"><a href="{{ route('storefront.home') }}">Home</a></li>
                        <li><a href="{{ route('storefront.shop') }}">Shop</a></li>
                        <li><a href="{{ route('storefront.vendors') }}">Vendors</a></li>
                        <li><a href="{{ route('storefront.become-vendor') }}">Sell on {{ config('app.name') }}</a></li>
                        <li><a href="{{ route('storefront.blog') }}">Blog</a></li>
                        <li>
                            <a href="{{ route('storefront.about') }}">Pages</a>
                            <ul>
                                <li><a href="{{ route('storefront.about') }}">About Us</a></li>
                                <li><a href="{{ route('storefront.contact') }}">Contact Us</a></li>
                                <li><a href="{{ route('storefront.faq') }}">FAQs</a></li>
                                <li><a href="{{ route('storefront.wishlist') }}">Wishlist</a></li>
                                <li><a href="{{ route('storefront.cart') }}">Cart</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="header-right ml-4">
                <a class="wishlist label-down link d-xs-show" href="{{ route('storefront.wishlist') }}">
                    <i class="w-icon-heart"></i>
                    <span class="wishlist-label d-lg-show">Wishlist</span>
                </a>
                @livewire('storefront.header.cart-icon')
            </div>
        </div>
    </div>

    <div class="header-bottom sticky-content fix-top sticky-header">
        <div class="container">
            <div class="inner-wrap">
                <div class="header-left flex-1">
                    <div class="dropdown category-dropdown has-border" data-visible="true">
                        <a href="#" class="category-toggle" role="button" data-toggle="dropdown" title="Browse Categories">
                            <i class="w-icon-category"></i>
                            <span>Browse Categories</span>
                        </a>
                        <div class="dropdown-box">
                            <ul class="menu vertical-menu category-menu">
                                @foreach ($headerCategories ?? [] as $category)
                                    <li><a href="{{ route('storefront.category', $category['id']) }}">{{ $category['name'] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <form action="{{ route('storefront.shop') }}" method="GET" class="header-search hs-expanded hs-round d-none d-md-flex input-wrapper mr-4 ml-4">
                        <input type="text" class="form-control" name="search" id="search" placeholder="Search products…" />
                        <button class="btn btn-search" type="submit"><i class="w-icon-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
