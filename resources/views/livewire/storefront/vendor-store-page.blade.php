<div>
    @if (!empty($vendor['banner_url']))
        <div class="vendor-banner" style="background-image:url('{{ $vendor['banner_url'] }}');height:220px;background-size:cover;background-position:center;"></div>
    @endif

    <div class="container py-6">
        <div class="d-flex align-items-center mb-6" style="gap:16px;">
            @if (!empty($vendor['logo_url']))
                <img src="{{ $vendor['logo_url'] }}" alt="{{ $vendor['business_name'] }}" width="80" height="80" class="rounded-circle">
            @endif
            <div>
                <h1 class="mb-1">{{ $vendor['business_name'] }}</h1>
                <p class="text-muted mb-0">{{ $vendor['description'] }}</p>
            </div>
        </div>

        <div class="row">
            @forelse ($products as $product)
                <div class="col-6 col-md-3 mb-4">
                    <x-storefront.product-card :product="$product" />
                </div>
            @empty
                <div class="col-12"><p>This vendor has no products listed yet.</p></div>
            @endforelse
        </div>
    </div>
</div>
