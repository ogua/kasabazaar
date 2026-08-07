<div>
    @if ($banners->isNotEmpty())
        <div class="intro-slider-container mb-6" wire:ignore>
            <div class="swiper intro-slider">
                <div class="swiper-wrapper">
                    @foreach ($banners as $banner)
                        <div class="swiper-slide">
                            <div class="intro-slide">
                                <a href="{{ $banner->link_url ?? '#' }}">
                                    <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="w-100" />
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container">
        @if (count($categories))
            <section class="mb-8">
                <h2 class="title title-simple">Shop by Category</h2>
                <div class="row">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-3 col-lg-2 mb-4">
                            <a href="{{ route('storefront.category', $category['id']) }}" class="category-item text-center d-block">
                                <figure class="category-media mb-2">
                                    <img src="{{ $category['image_url'] ?? asset('storefront/assets/images/lazy.png') }}" alt="{{ $category['name'] }}" width="80" height="80" class="rounded-circle" />
                                </figure>
                                <span>{{ $category['name'] }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($featured))
            <section class="mb-8">
                <h2 class="title title-simple">Featured Products</h2>
                <div class="row">
                    @foreach ($featured as $product)
                        <div class="col-6 col-md-4 col-lg-3 mb-4">
                            <x-storefront.product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($newArrivals))
            <section class="mb-8">
                <h2 class="title title-simple">New Arrivals</h2>
                <div class="row">
                    @foreach ($newArrivals as $product)
                        <div class="col-6 col-md-4 col-lg-3 mb-4">
                            <x-storefront.product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($bestSellers))
            <section class="mb-8">
                <h2 class="title title-simple">Best Sellers</h2>
                <div class="row">
                    @foreach ($bestSellers as $product)
                        <div class="col-6 col-md-4 col-lg-3 mb-4">
                            <x-storefront.product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
