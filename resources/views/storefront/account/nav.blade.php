<div class="col-md-3 mb-6">
    <div class="list-group">
        <a href="{{ route('storefront.account.dashboard') }}" class="list-group-item {{ request()->routeIs('storefront.account.dashboard') ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('storefront.account.orders') }}" class="list-group-item {{ request()->routeIs('storefront.account.orders*') ? 'active' : '' }}">Orders</a>
        <a href="{{ route('storefront.account.addresses') }}" class="list-group-item {{ request()->routeIs('storefront.account.addresses') ? 'active' : '' }}">Addresses</a>
        <a href="{{ route('storefront.account.profile') }}" class="list-group-item {{ request()->routeIs('storefront.account.profile') ? 'active' : '' }}">Account Details</a>
        <a href="{{ route('storefront.wishlist') }}" class="list-group-item">Wishlist</a>
        <form method="POST" action="{{ route('storefront.logout') }}">
            @csrf
            <button type="submit" class="list-group-item text-left w-100 border-0">Sign Out</button>
        </form>
    </div>
</div>
