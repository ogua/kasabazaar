<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    @php
        $activeCategory = collect($headerCategories ?? [])->firstWhere('id', $category_id);
    @endphp

    <x-storefront.ui.breadcrumb :items="array_filter([
        ['label' => 'Shop', 'href' => $activeCategory ? route('storefront.shop') : null],
        $activeCategory ? ['label' => $activeCategory['name']] : null,
    ])" />

    <div class="flex flex-wrap items-end justify-between gap-2 mb-5">
        <h1 class="font-display font-bold text-2xl text-navy-900">{{ $activeCategory['name'] ?? 'All Products' }}</h1>
        <p class="text-sm text-muted tabular-nums" wire:loading.class="opacity-50" wire:target="search,category_id,price_min,price_max,in_stock,sort,gotoPage,previousPage,nextPage">
            {{ number_format($meta['total'] ?? count($products)) }} {{ Str::plural('result', $meta['total'] ?? count($products)) }}
        </p>
    </div>

    <div class="grid lg:grid-cols-[240px_minmax(0,1fr)] gap-6 items-start" x-data="{ filtersOpen: false }">
        <aside class="lg:sticky lg:top-32">
            <button
                type="button"
                @click="filtersOpen = !filtersOpen"
                :aria-expanded="filtersOpen"
                class="lg:hidden flex w-full items-center justify-between rounded-lg border border-border bg-surface px-4 h-11 text-sm font-semibold text-navy-900"
            >
                <span class="flex items-center gap-2"><x-storefront.icon name="menu" class="w-4 h-4" /> Filters</span>
                <x-storefront.icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="filtersOpen && 'rotate-180'" />
            </button>
            <div class="hidden lg:block mt-3 lg:mt-0 bg-surface border border-border rounded-lg divide-y divide-border [&>div]:p-4" :class="filtersOpen && 'block!'">
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

                <div>
                    <label class="flex items-center gap-2 text-sm text-fg cursor-pointer">
                        <input type="checkbox" wire:model.live="in_stock" class="w-4 h-4 rounded-sm border-border accent-accent">
                        In stock only
                    </label>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <div class="flex items-center justify-between gap-4 mb-3 bg-surface border border-border rounded-lg px-4 h-12">
                <p class="text-sm text-muted truncate">
                    @if ($search)
                        Results for <span class="font-semibold text-fg">“{{ $search }}”</span>
                    @else
                        Showing {{ $activeCategory ? 'products in '.$activeCategory['name'] : 'all products' }}
                    @endif
                </p>
                <label for="listing-sort" class="sr-only">Sort by</label>
                <select id="listing-sort" wire:model.live="sort" class="border border-border rounded-sm px-3 py-1.5 text-sm bg-surface max-w-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
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
                class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 transition-opacity"
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
