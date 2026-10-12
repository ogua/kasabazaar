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
    <div class="bg-surface-muted pb-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
            <x-storefront.ui.breadcrumb :items="[
                ['label' => 'Vendors', 'href' => route('storefront.vendors')],
                ['label' => $vendor['business_name']],
            ]" />

            <section class="bg-surface border border-border rounded-lg overflow-hidden mb-4">
                @if (! empty($vendor['banner_url']))
                    <div class="h-36 md:h-52 bg-navy-900 bg-cover bg-center" style="background-image:url('{{ $vendor['banner_url'] }}')"></div>
                @else
                    <div class="h-20 md:h-24 bg-navy-900"></div>
                @endif
                <div class="flex flex-col sm:flex-row sm:items-end gap-4 px-4 sm:px-6 pb-5 -mt-10">
                    <span class="flex items-center justify-center w-20 h-20 md:w-24 md:h-24 rounded-full bg-surface overflow-hidden shrink-0 ring-4 ring-surface border border-border">
                        <img
                            src="{{ $vendor['logo_url'] ?? asset('images/product-placeholder.png') }}"
                            alt="{{ $vendor['business_name'] }}"
                            class="w-full h-full object-cover"
                            onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"
                        >
                    </span>
                    <div class="flex-1 min-w-0">
                        <h1 class="font-display font-bold text-2xl text-navy-900">{{ $vendor['business_name'] }}</h1>
                        @if (! empty($vendor['description']))
                            <p class="text-muted text-sm mt-1 max-w-2xl">{{ $vendor['description'] }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center gap-1.5 self-start sm:self-end rounded-sm bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
                        <x-storefront.icon name="shield-check" class="w-4 h-4" />
                        Vetted vendor
                    </span>
                </div>
            </section>

            @if ($error)
                <x-storefront.ui.alert variant="error" class="mb-4">{{ $error }}</x-storefront.ui.alert>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3" wire:loading.class="opacity-50" wire:target="gotoPage,previousPage,nextPage">
                @forelse ($products as $product)
                    <x-storefront.product-card :product="$product" />
                @empty
                    @unless ($error)
                        <div class="col-span-full">
                            <x-storefront.ui.empty-state icon="box" title="No products yet" description="This vendor hasn't listed any products yet." class="bg-surface" />
                        </div>
                    @endunless
                @endforelse
            </div>

            <x-storefront.ui.pagination :meta="$meta" />
        </div>
    </div>
@endif
