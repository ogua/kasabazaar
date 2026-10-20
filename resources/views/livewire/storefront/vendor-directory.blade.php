<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Vendors']]" />

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-navy-900 text-white rounded-lg px-6 py-6 mb-4">
            <div>
                <h1 class="font-display font-bold text-2xl mb-1">Shop by vendor</h1>
                <p class="text-white/70 text-sm max-w-xl">Every store on {{ config('app.name') }} is an independent, vetted local business.</p>
            </div>
            <x-storefront.ui.button href="{{ route('storefront.become-vendor') }}" variant="accent" class="shrink-0">
                <x-storefront.icon name="store" class="w-4 h-4" />
                Open your store
            </x-storefront.ui.button>
        </div>

        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-4">{{ $error }}</x-storefront.ui.alert>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3" wire:loading.class="opacity-50" wire:target="gotoPage,previousPage,nextPage">
            @forelse ($vendors as $vendor)
                <a href="{{ route('storefront.vendor', $vendor['slug']) }}" class="group flex flex-col items-center text-center bg-surface border border-border rounded-lg p-4 hover:border-navy-200 hover:shadow-float transition">
                    <span class="flex items-center justify-center w-20 h-20 rounded-full bg-surface-muted overflow-hidden mb-3 ring-1 ring-border">
                        <img
                            src="{{ $vendor['logo_url'] ?? asset('images/product-placeholder.png') }}"
                            alt="{{ $vendor['business_name'] }}"
                            loading="lazy"
                            class="w-full h-full object-cover"
                            onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';"
                        >
                    </span>
                    <span class="text-sm font-semibold text-fg group-hover:text-accent line-clamp-2 mb-3">{{ $vendor['business_name'] }}</span>
                    <span class="mt-auto inline-flex items-center gap-1 text-xs font-semibold text-navy-500 group-hover:text-accent">
                        Visit store
                        <x-storefront.icon name="chevron-right" class="w-3.5 h-3.5" />
                    </span>
                </a>
            @empty
                @unless ($error)
                    <div class="col-span-full">
                        <x-storefront.ui.empty-state icon="building" title="No vendors yet" description="Check back soon as new vendors join the marketplace." class="bg-surface" />
                    </div>
                @endunless
            @endforelse
        </div>

        <x-storefront.ui.pagination :meta="$meta" />
    </div>
</div>
