<div class="container mt-6 mb-8">
    <div class="row">
        <aside class="col-lg-3 mb-6">
            <div class="widget widget-clean">
                <h4 class="widget-title">Search</h4>
                <input type="text" class="form-control" wire:model.live.debounce.500ms="search" placeholder="Search products…" />
            </div>

            <div class="widget widget-clean mt-6">
                <h4 class="widget-title">Price Range (GHS)</h4>
                <div class="d-flex" style="gap:8px;">
                    <input type="number" class="form-control" wire:model.live.debounce.500ms="price_min" placeholder="Min" />
                    <input type="number" class="form-control" wire:model.live.debounce.500ms="price_max" placeholder="Max" />
                </div>
            </div>

            <div class="widget widget-clean mt-6">
                <label class="d-flex align-items-center">
                    <input type="checkbox" wire:model.live="in_stock" class="mr-2" /> In stock only
                </label>
            </div>
        </aside>

        <div class="col-lg-9">
            <div class="toolbox d-flex justify-content-between align-items-center mb-4">
                <span>{{ $meta['total'] ?? count($products) }} products found</span>
                <select class="form-control" style="max-width:220px;" wire:model.live="sort">
                    <option value="newest">Newest</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="popular">Most Popular</option>
                </select>
            </div>

            <div class="row" wire:loading.class="opacity-50">
                @forelse ($products as $product)
                    <div class="col-6 col-md-4 mb-4">
                        <x-storefront.product-card :product="$product" />
                    </div>
                @empty
                    <div class="col-12 text-center py-8">
                        <p>No products found. Try adjusting your filters.</p>
                    </div>
                @endforelse
            </div>

            @if (($meta['last_page'] ?? 1) > 1)
                <ul class="pagination justify-content-center mt-6">
                    @for ($page = 1; $page <= $meta['last_page']; $page++)
                        <li class="page-item {{ $page === ($meta['current_page'] ?? 1) ? 'active' : '' }}">
                            <button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                        </li>
                    @endfor
                </ul>
            @endif
        </div>
    </div>
</div>
