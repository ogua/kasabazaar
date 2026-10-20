<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Compare products']]" />

        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <div>
                <h1 class="font-display font-bold text-2xl text-navy-900">Compare products</h1>
                <p class="text-sm text-muted">Up to {{ $max }} products side by side. Lowest price and best rating are highlighted.</p>
            </div>
            <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="secondary" size="sm">
                <x-storefront.icon name="plus" class="w-4 h-4" /> Add products
            </x-storefront.ui.button>
        </div>

        @if (count($products) === 0)
            <x-storefront.ui.empty-state
                icon="arrows-right-left"
                title="Nothing to compare yet"
                description="Use the compare button on any product to add it here — pick two to four similar items."
                class="bg-surface"
            >
                <x-slot:action>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Browse products</x-storefront.ui.button>
                </x-slot:action>
            </x-storefront.ui.empty-state>
        @else
            <div class="bg-surface border border-border rounded-lg overflow-x-auto" wire:loading.class="opacity-60">
                <table class="w-full min-w-[640px] text-sm border-collapse">
                    <caption class="sr-only">Product comparison</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-40 p-4 text-left align-bottom text-xs uppercase tracking-wide text-muted font-semibold">Product</th>
                            @foreach ($products as $product)
                                @php $image = $product['images'][0]['url'] ?? null; @endphp
                                <th scope="col" class="p-4 align-top text-left font-normal border-l border-border" wire:key="compare-head-{{ $product['id'] }}">
                                    <div class="relative">
                                        <button type="button" wire:click="remove('{{ $product['id'] }}')" class="absolute -top-1 -right-1 flex items-center justify-center w-7 h-7 rounded-full bg-surface border border-border text-muted hover:text-error" aria-label="Remove {{ $product['name'] }}">
                                            <x-storefront.icon name="x-mark" class="w-4 h-4" />
                                        </button>
                                        <a href="{{ route('storefront.product', $product['id']) }}" class="block aspect-square max-w-40 rounded-sm bg-surface-muted overflow-hidden mb-3">
                                            <img src="{{ $image ?? asset('images/product-placeholder.png') }}" alt="" class="w-full h-full object-cover" onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';">
                                        </a>
                                        <a href="{{ route('storefront.product', $product['id']) }}" class="font-medium text-fg hover:text-accent line-clamp-2 leading-snug">{{ $product['name'] }}</a>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Price</th>
                            @foreach ($products as $product)
                                @php
                                    $price = (float) ($product['discount_price_ghs'] ?? $product['price_ghs'] ?? 0);
                                    $isLowest = $lowestPrice !== null && $price === (float) $lowestPrice;
                                @endphp
                                <td class="p-4 border-l border-border {{ $isLowest ? 'bg-success/5' : '' }}">
                                    <span class="font-display font-bold text-lg text-navy-900 tabular-nums">GHS {{ number_format($price, 2) }}</span>
                                    @if (! empty($product['discount_price_ghs']))
                                        <span class="block text-xs text-muted line-through tabular-nums">GHS {{ number_format($product['price_ghs'], 2) }}</span>
                                    @endif
                                    @if ($isLowest)
                                        <span class="mt-1 inline-block rounded-sm bg-success/10 px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-success">Lowest price</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Rating</th>
                            @foreach ($products as $product)
                                @php
                                    $count = (int) ($product['reviews_count'] ?? 0);
                                    $rating = $count > 0 ? (float) $product['average_rating'] : null;
                                    $isBest = $bestRating !== null && $rating === (float) $bestRating;
                                @endphp
                                <td class="p-4 border-l border-border {{ $isBest ? 'bg-success/5' : '' }}">
                                    @if ($rating !== null)
                                        <span class="flex items-center gap-1">
                                            <span class="flex text-warning" aria-hidden="true">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <x-storefront.icon :name="$i <= round($rating) ? 'star' : 'star-outline'" class="w-4 h-4" />
                                                @endfor
                                            </span>
                                            <span class="text-muted tabular-nums">{{ number_format($rating, 1) }} ({{ $count }})</span>
                                        </span>
                                        @if ($isBest)
                                            <span class="mt-1 inline-block rounded-sm bg-success/10 px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-success">Best rated</span>
                                        @endif
                                    @else
                                        <span class="text-muted">No reviews yet</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Availability</th>
                            @foreach ($products as $product)
                                @php $stock = (int) ($product['stock'] ?? 0); @endphp
                                <td class="p-4 border-l border-border">
                                    @if ($stock > 0)
                                        <span class="font-medium text-success">In stock</span> <span class="text-muted">· {{ $stock }}</span>
                                    @else
                                        <span class="font-medium text-error">Out of stock</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Sold by</th>
                            @foreach ($products as $product)
                                <td class="p-4 border-l border-border">
                                    @if (! empty($product['vendor']['business_name']))
                                        <a href="{{ route('storefront.vendor', $product['vendor']['slug']) }}" class="text-navy-500 hover:text-accent">{{ $product['vendor']['business_name'] }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Category</th>
                            @foreach ($products as $product)
                                <td class="p-4 border-l border-border text-fg">{{ $product['category']['name'] ?? '—' }}</td>
                            @endforeach
                        </tr>
                        @foreach ($specKeys as $key)
                            <tr>
                                <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">{{ $specLabel($key) }}</th>
                                @foreach ($products as $product)
                                    @php $value = $product['specifications'][$key] ?? null; @endphp
                                    <td class="p-4 border-l border-border {{ blank($value) ? 'text-muted' : 'text-fg' }}">
                                        {{ is_array($value) ? implode(', ', $value) : (blank($value) ? '—' : $value) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row" class="p-4 text-left text-xs uppercase tracking-wide text-muted font-semibold">Description</th>
                            @foreach ($products as $product)
                                <td class="p-4 border-l border-border text-muted text-xs leading-relaxed align-top">{{ Str::limit($product['description'] ?? '', 220) ?: '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td></td>
                            @foreach ($products as $product)
                                <td class="p-4 border-l border-border" x-data="{ adding: false }" @toast.window="adding = false">
                                    <button
                                        type="button"
                                        x-on:click="adding = true"
                                        wire:click="$dispatch('add-to-cart', { productId: '{{ $product['id'] }}' })"
                                        :disabled="adding || @js(($product['stock'] ?? 0) <= 0)"
                                        class="w-full h-10 inline-flex items-center justify-center gap-2 rounded-sm bg-accent text-white text-sm font-semibold hover:bg-accent-hover disabled:opacity-50"
                                    >
                                        <template x-if="adding"><x-storefront.ui.spinner class="w-4 h-4" /></template>
                                        <template x-if="!adding"><x-storefront.icon name="cart" class="w-4 h-4" /></template>
                                        Add to cart
                                    </button>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            @if (count($products) < 2)
                <p class="mt-3 text-sm text-muted">Add at least one more product to see a useful comparison.</p>
            @endif
        @endif
    </div>
</div>
