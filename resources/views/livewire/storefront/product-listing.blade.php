<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    @php
        $activeCategory = collect($headerCategories ?? [])->firstWhere('id', $category_id);
    @endphp

    <x-storefront.ui.breadcrumb :items="array_filter([
        ['label' => 'Shop', 'href' => $activeCategory ? route('storefront.shop') : null],
        $activeCategory ? ['label' => $activeCategory['name']] : null,
    ])" />

    <div class="grid lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
            <div class="lg:sticky lg:top-16 space-y-6">
                <div>
                    <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Search</h3>
                    <div class="relative">
                        <x-storefront.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
                        <input
                            type="text"
                            wire:model.live.debounce.500ms="search"
                            placeholder="Search products..."
                            class="w-full border border-border rounded-sm pl-9 pr-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                        >
                    </div>
                </div>

                <div>
                    <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Category</h3>
                    <select wire:model.live="category_id" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        <option value="">All Categories</option>
                        @foreach ($headerCategories ?? [] as $category)
                            <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <h3 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Price Range (GHS)</h3>
                    <div class="flex items-center gap-2">
                        <input type="number" wire:model.live.debounce.500ms="price_min" placeholder="Min" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        <span class="text-muted">&ndash;</span>
                        <input type="number" wire:model.live.debounce.500ms="price_max" placeholder="Max" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-fg cursor-pointer">
                    <input type="checkbox" wire:model.live="in_stock" class="w-4 h-4 rounded-sm border-border text-accent focus:ring-accent">
                    In stock only
                </label>
            </div>
        </aside>

        <div class="lg:col-span-3">
            <div class="flex items-center justify-between gap-4 mb-6">
                <p class="text-sm text-muted" wire:loading.class="opacity-50" wire:target="search,category_id,price_min,price_max,in_stock,sort,gotoPage,previousPage,nextPage">
                    {{ $meta['total'] ?? count($products) }} products found
                </p>
                <select wire:model.live="sort" class="border border-border rounded-sm px-3 py-2 text-sm max-w-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="popular">Most Popular</option>
                </select>
            </div>

            @if ($error)
                <x-storefront.ui.alert variant="error" class="mb-6">{{ $error }}</x-storefront.ui.alert>
            @endif

            <div
                class="grid grid-cols-2 md:grid-cols-3 gap-5 transition-opacity"
                wire:loading.class="opacity-40"
                wire:target="search,category_id,price_min,price_max,in_stock,sort,gotoPage,previousPage,nextPage"
            >
                @forelse ($products as $product)
                    <x-storefront.product-card :product="$product" />
                @empty
                    @unless ($error)
                        <div class="col-span-full">
                            <x-storefront.ui.empty-state
                                icon="search"
                                title="No products found"
                                description="Try adjusting your search or filters to find what you're looking for."
                            />
                        </div>
                    @endunless
                @endforelse
            </div>

            <x-storefront.ui.pagination :meta="$meta" />
        </div>
    </div>
</div>
