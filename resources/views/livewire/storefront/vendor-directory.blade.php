<div class="container py-8">
    <h1 class="title title-simple mb-6">All Vendors</h1>

    <div class="row">
        @forelse ($vendors as $vendor)
            <div class="col-md-4 mb-4">
                <a href="{{ route('storefront.vendor', $vendor['slug']) }}" class="d-block border rounded p-4 text-center">
                    @if ($vendor['logo_url'])
                        <img src="{{ $vendor['logo_url'] }}" alt="{{ $vendor['business_name'] }}" width="80" height="80" class="rounded-circle mb-3">
                    @endif
                    <h4>{{ $vendor['business_name'] }}</h4>
                    <p class="text-muted">{{ $vendor['products_count'] }} products</p>
                </a>
            </div>
        @empty
            <div class="col-12"><p>No vendors yet.</p></div>
        @endforelse
    </div>
</div>
