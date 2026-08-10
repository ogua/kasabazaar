<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <x-storefront.ui.breadcrumb :items="[['label' => 'Vendors']]" />

    <h1 class="font-display font-bold text-2xl text-navy-900 mb-8">All Vendors</h1>

    @if ($error)
        <x-storefront.ui.alert variant="error" class="mb-8">{{ $error }}</x-storefront.ui.alert>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5" wire:loading.class="opacity-50" wire:target="gotoPage,previousPage,nextPage">
        @forelse ($vendors as $vendor)
            <a href="{{ route('storefront.vendor', $vendor['slug']) }}" class="group flex flex-col items-center text-center bg-surface border border-border rounded-lg p-5 hover:shadow-float transition-shadow">
                <span class="flex items-center justify-center w-20 h-20 rounded-full bg-surface-muted overflow-hidden mb-3">
                    <img
                        src="{{ $vendor['logo_url'] ?? asset('images/product-placeholder.png') }}"
                        alt="{{ $vendor['business_name'] }}"
                        loading="lazy"
                        class="w-full h-full object-cover"
                        onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
                    >
                </span>
                <span class="text-sm font-medium text-fg group-hover:text-accent line-clamp-2">{{ $vendor['business_name'] }}</span>
            </a>
        @empty
            @unless ($error)
                <div class="col-span-full">
                    <x-storefront.ui.empty-state icon="building" title="No vendors yet" description="Check back soon as new vendors join the marketplace." />
                </div>
            @endunless
        @endforelse
    </div>

    <x-storefront.ui.pagination :meta="$meta" />
</div>
