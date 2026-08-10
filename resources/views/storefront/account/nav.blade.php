@php
    $links = [
        ['label' => 'Dashboard', 'route' => 'storefront.account.dashboard', 'active' => 'storefront.account.dashboard', 'icon' => 'grid'],
        ['label' => 'Orders', 'route' => 'storefront.account.orders', 'active' => 'storefront.account.orders*', 'icon' => 'box'],
        ['label' => 'Addresses', 'route' => 'storefront.account.addresses', 'active' => 'storefront.account.addresses', 'icon' => 'map-pin'],
        ['label' => 'Account Details', 'route' => 'storefront.account.profile', 'active' => 'storefront.account.profile', 'icon' => 'user'],
        ['label' => 'Wishlist', 'route' => 'storefront.wishlist', 'active' => 'storefront.wishlist', 'icon' => 'heart'],
    ];
@endphp

<nav class="bg-surface border border-border rounded-lg overflow-hidden">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route']) }}"
            class="flex items-center gap-3 px-4 py-3 text-sm border-b border-border last:border-b-0 {{ request()->routeIs($link['active']) ? 'bg-navy-900 text-white font-semibold' : 'text-fg hover:bg-surface-muted' }}"
        >
            <x-storefront.icon :name="$link['icon']" class="w-4 h-4 shrink-0" />
            {{ $link['label'] }}
        </a>
    @endforeach
    <form method="POST" action="{{ route('storefront.logout') }}">
        @csrf
        <button type="submit" class="flex items-center gap-3 px-4 py-3 text-sm text-error hover:bg-error/5 w-full text-left">
            <x-storefront.icon name="x-mark" class="w-4 h-4 shrink-0" />
            Sign Out
        </button>
    </form>
</nav>
