@if ($notFound)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
        <x-storefront.ui.empty-state
            icon="building"
            title="Vendor not found"
            description="This vendor may no longer be active or the link is incorrect."
        >
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.vendors') }}" variant="primary">Browse All Vendors</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    </div>
@else
    <div>
        @if (! empty($vendor['banner_url']))
            <div class="h-48 md:h-64 bg-navy-900 bg-cover bg-center" style="background-image:url('{{ $vendor['banner_url'] }}')"></div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
            <x-storefront.ui.breadcrumb :items="[
                ['label' => 'Vendors', 'href' => route('storefront.vendors')],
                ['label' => $vendor['business_name']],
            ]" />

            <div class="flex items-center gap-4 mb-10">
                <span class="flex items-center justify-center w-20 h-20 rounded-full bg-surface-muted overflow-hidden shrink-0 border border-border">
                    <img
                        src="{{ $vendor['logo_url'] ?? asset('images/product-placeholder.png') }}"
                        alt="{{ $vendor['business_name'] }}"
                        class="w-full h-full object-cover"
                        onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
                    >
                </span>
                <div>
                    <h1 class="font-display font-bold text-2xl text-navy-900">{{ $vendor['business_name'] }}</h1>
                    @if (! empty($vendor['description']))
                        <p class="text-muted text-sm mt-1 max-w-xl">{{ $vendor['description'] }}</p>
                    @endif
                </div>
            </div>

            @if ($error)
                <x-storefront.ui.alert variant="error" class="mb-8">{{ $error }}</x-storefront.ui.alert>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-5" wire:loading.class="opacity-50" wire:target="gotoPage,previousPage,nextPage">
                @forelse ($products as $product)
                    <x-storefront.product-card :product="$product" />
                @empty
                    @unless ($error)
                        <div class="col-span-full">
                            <x-storefront.ui.empty-state icon="box" title="No products yet" description="This vendor hasn't listed any products yet." />
                        </div>
                    @endunless
                @endforelse
            </div>

            <x-storefront.ui.pagination :meta="$meta" />
        </div>
    </div>
@endif
