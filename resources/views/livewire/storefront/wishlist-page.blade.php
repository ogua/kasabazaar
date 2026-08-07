<div class="container py-8">
    <h1 class="title title-simple mb-6">My Wishlist</h1>

    @guest
        <p><a href="{{ route('storefront.login') }}">Sign in</a> to see your wishlist.</p>
    @else
        @if (empty($products))
            <p>Your wishlist is empty. <a href="{{ route('storefront.shop') }}">Browse products</a>.</p>
        @else
            <div class="row">
                @foreach ($products as $product)
                    <div class="col-6 col-md-3 mb-4">
                        <x-storefront.product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        @endif
    @endguest
</div>
