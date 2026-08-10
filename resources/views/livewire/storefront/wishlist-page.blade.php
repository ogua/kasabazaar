<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="font-display font-bold text-2xl text-navy-900 mb-8">My Wishlist</h1>

    @guest
        <x-storefront.ui.empty-state icon="heart" title="Sign in to see your wishlist" description="Save products you love and find them here anytime.">
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.login') }}" variant="primary">Sign In</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    @else
        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-8">{{ $error }}</x-storefront.ui.alert>
        @endif

        @if (empty($products))
            @unless ($error)
                <x-storefront.ui.empty-state icon="heart" title="Your wishlist is empty" description="Browse products and tap the heart icon to save them here.">
                    <x-slot:action>
                        <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Browse Products</x-storefront.ui.button>
                    </x-slot:action>
                </x-storefront.ui.empty-state>
            @endunless
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                @foreach ($products as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    @endguest
</div>
