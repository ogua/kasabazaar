<div class="relative" x-data="{ open: false }" @click.outside="open = false" wire:key="cart-icon">
    <button @click="open = !open" :aria-expanded="open" class="flex items-center gap-2 p-2 rounded-sm text-navy-900 hover:bg-surface-muted relative" aria-label="Cart, {{ $count }} {{ Str::plural('item', $count) }}">
        <span class="relative">
            <x-storefront.icon name="cart" class="w-6 h-6" />
            @if ($count > 0)
                <span class="absolute -top-2 -right-2 flex items-center justify-center min-w-4.5 h-4.5 px-1 rounded-full bg-accent text-white text-[10px] font-bold tabular-nums">{{ $count }}</span>
            @endif
        </span>
        <span class="hidden xl:flex flex-col items-start leading-tight text-left">
            <span class="text-[11px] text-muted tabular-nums">GHS {{ number_format($subtotalGhs, 2) }}</span>
            <span class="text-sm font-semibold">Cart</span>
        </span>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
        class="absolute right-0 top-full mt-2 w-72 bg-white border border-border rounded-lg shadow-float p-4 z-40"
    >
        <div class="flex items-center justify-between text-sm mb-4">
            <span class="text-muted">Subtotal ({{ $count }} {{ Str::plural('item', $count) }})</span>
            <span class="font-display font-semibold text-navy-900 tabular-nums">GHS {{ number_format($subtotalGhs, 2) }}</span>
        </div>
        <div class="flex gap-2">
            <x-storefront.ui.button href="{{ route('storefront.cart') }}" variant="secondary" size="sm" class="flex-1">View Cart</x-storefront.ui.button>
            <x-storefront.ui.button href="{{ route('storefront.checkout') }}" variant="accent" size="sm" class="flex-1">Checkout</x-storefront.ui.button>
        </div>
    </div>
</div>
