<div>
    @if (count($items) && ! request()->routeIs('storefront.compare'))
        {{-- Keeps the end of the page clear of the fixed tray. --}}
        <div class="h-16" aria-hidden="true"></div>
        <div
            class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-surface shadow-float"
            x-data="{ open: true }"
            role="region"
            aria-label="Products to compare"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center gap-3">
                <button type="button" @click="open = ! open" class="flex items-center gap-2 text-sm font-semibold text-navy-900 shrink-0" :aria-expanded="open">
                    <x-storefront.icon name="arrows-right-left" class="w-5 h-5 text-accent" />
                    <span>Compare <span class="tabular-nums">({{ count($items) }}/{{ $max }})</span></span>
                    <x-storefront.icon name="chevron-down" class="w-4 h-4 text-muted transition-transform" x-bind:class="open || 'rotate-180'" />
                </button>

                <ul x-show="open" class="hidden sm:flex items-center gap-2 flex-1 min-w-0 overflow-x-auto">
                    @foreach ($items as $item)
                        <li class="flex items-center gap-2 rounded-sm border border-border bg-surface-muted pl-1 pr-2 py-1 max-w-56 shrink-0" wire:key="compare-tray-{{ $item['id'] }}">
                            <img src="{{ $item['image'] ?: asset('images/product-placeholder.png') }}" alt="" class="w-8 h-8 rounded-sm object-cover" onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';">
                            <span class="text-xs text-fg truncate">{{ $item['name'] }}</span>
                            <button type="button" wire:click="remove('{{ $item['id'] }}')" class="text-muted hover:text-error shrink-0" aria-label="Remove {{ $item['name'] }} from comparison">
                                <x-storefront.icon name="x-mark" class="w-4 h-4" />
                            </button>
                        </li>
                    @endforeach
                    @for ($i = count($items); $i < $max; $i++)
                        <li class="hidden md:flex items-center justify-center w-28 h-10 rounded-sm border border-dashed border-border text-[11px] text-muted shrink-0">Add a product</li>
                    @endfor
                </ul>

                <div class="flex items-center gap-2 ml-auto shrink-0">
                    <button type="button" wire:click="clear" class="text-xs font-semibold text-muted hover:text-error">Clear</button>
                    @if (count($items) >= 2)
                        <x-storefront.ui.button href="{{ route('storefront.compare') }}" variant="accent" size="sm">Compare now</x-storefront.ui.button>
                    @else
                        <span class="text-xs text-muted">Add one more to compare</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
