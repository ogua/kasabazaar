<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vendor Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('storefront/assets/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storefront/assets/css/demo2.min.css') }}">
    @livewireStyles
    <style>
        body { background: #f4f5f7; }
        .vendor-shell { display: flex; min-height: 100vh; }
        .vendor-sidebar { width: 240px; background: #1f2430; color: #fff; flex-shrink: 0; }
        .vendor-sidebar a { display: block; padding: 12px 20px; color: #cfd3dc; text-decoration: none; }
        .vendor-sidebar a.active, .vendor-sidebar a:hover { background: #2b3140; color: #fff; }
        .vendor-main { flex: 1; padding: 24px; }
        .vendor-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    </style>
</head>
<body>
    <div class="vendor-shell">
        <aside class="vendor-sidebar">
            <div style="padding:20px;font-weight:bold;font-size:18px;">{{ config('app.name') }} Vendor</div>
            <a href="{{ route('vendor.dashboard') }}" class="{{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">Overview</a>
            <a href="{{ route('vendor.products.index') }}" class="{{ request()->routeIs('vendor.products.*') ? 'active' : '' }}">Products</a>
            <a href="{{ route('vendor.orders.index') }}" class="{{ request()->routeIs('vendor.orders.*') ? 'active' : '' }}">Orders</a>
            <a href="{{ route('vendor.earnings') }}" class="{{ request()->routeIs('vendor.earnings') ? 'active' : '' }}">Earnings</a>
            <a href="{{ route('vendor.reviews') }}" class="{{ request()->routeIs('vendor.reviews') ? 'active' : '' }}">Reviews</a>
            <a href="{{ route('vendor.coupons.index') }}" class="{{ request()->routeIs('vendor.coupons.*') ? 'active' : '' }}">Coupons</a>
            <a href="{{ route('vendor.store-settings') }}" class="{{ request()->routeIs('vendor.store-settings') ? 'active' : '' }}">Store Settings</a>
            <form method="POST" action="{{ route('vendor.logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="border-0 bg-transparent" style="color:#cfd3dc;padding:12px 20px;width:100%;text-align:left;">Sign Out</button>
            </form>
        </aside>
        <main class="vendor-main">
            <div class="vendor-topbar">
                <h1 class="h4 mb-0">@yield('title', 'Overview')</h1>
                <span>{{ auth()->user()->name ?? '' }}</span>
            </div>
            {{ $slot ?? '' }}
        </main>
    </div>

    @livewire('storefront.shared.toast')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireScripts
</body>
</html>
