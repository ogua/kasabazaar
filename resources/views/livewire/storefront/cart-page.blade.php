@php $items = $cart['items'] ?? []; @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="font-display font-bold text-2xl text-navy-900 mb-8">Shopping Cart</h1>

    @if ($error)
        <x-storefront.ui.alert variant="error" class="mb-8">{{ $error }}</x-storefront.ui.alert>
    @endif

    @if (empty($items))
        @unless ($error)
            <x-storefront.ui.empty-state icon="cart" title="Your cart is empty" description="Looks like you haven't added anything yet.">
                <x-slot:action>
                    <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Continue Shopping</x-storefront.ui.button>
                </x-slot:action>
            </x-storefront.ui.empty-state>
        @endunless
    @else
        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="border border-border rounded-lg overflow-hidden" wire:loading.class="opacity-50" wire:target="updateQuantity,removeItem">
                    <table class="w-full text-sm">
                        <thead class="bg-surface-muted text-xs uppercase tracking-wide text-muted">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold">Product</th>
                                <th class="text-left px-4 py-3 font-semibold hidden sm:table-cell">Vendor</th>
                                <th class="text-right px-4 py-3 font-semibold">Price</th>
                                <th class="text-center px-4 py-3 font-semibold">Qty</th>
                                <th class="text-right px-4 py-3 font-semibold">Total</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($items as $item)
                                <tr wire:key="cart-item-{{ $item['id'] }}">
                                    <td class="px-4 py-4 font-medium text-fg">{{ $item['product']['name'] ?? 'Product' }}</td>
                                    <td class="px-4 py-4 text-muted hidden sm:table-cell">{{ $item['vendor']['business_name'] ?? '—' }}</td>
                                    <td class="px-4 py-4 text-right tabular-nums">GHS {{ number_format($item['price_ghs'], 2) }}</td>
                                    <td class="px-4 py-4">
                                        <input
                                            type="number"
                                            min="1"
                                            value="{{ $item['quantity'] }}"
                                            wire:change="updateQuantity('{{ $item['id'] }}', $event.target.value)"
                                            class="w-16 mx-auto block border border-border rounded-sm px-2 py-1.5 text-center tabular-nums focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                                        >
                                    </td>
                                    <td class="px-4 py-4 text-right font-semibold text-navy-900 tabular-nums">GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <button type="button" wire:click="removeItem('{{ $item['id'] }}')" wire:loading.attr="disabled" wire:target="removeItem('{{ $item['id'] }}')" class="text-muted hover:text-error" aria-label="Remove item">
                                            <x-storefront.icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm" class="mt-4">
                    <x-storefront.icon name="chevron-left" class="w-4 h-4" /> Continue Shopping
                </x-storefront.ui.button>
            </div>

            <div>
                <x-storefront.ui.card class="sticky top-16">
                    <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Order Summary</h2>

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-muted">Subtotal</span>
                            <span class="font-medium tabular-nums">GHS {{ number_format($cart['subtotal_ghs'] ?? 0, 2) }}</span>
                        </div>
                        @if (($cart['discount_ghs'] ?? 0) > 0)
                            <div class="flex justify-between text-success">
                                <span>Discount</span>
                                <span class="font-medium tabular-nums">-GHS {{ number_format($cart['discount_ghs'], 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <form wire:submit.prevent="applyCoupon" wire:loading.attr="disabled" wire:target="applyCoupon" class="flex gap-2 mb-2">
                        <input type="text" wire:model="couponCode" placeholder="Coupon code" class="flex-1 border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                        <x-storefront.ui.button type="submit" variant="secondary" size="sm" wire:loading.attr="disabled" wire:target="applyCoupon">
                            <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                            <x-storefront.ui.spinner class="w-4 h-4" wire:loading wire:target="applyCoupon" />
                        </x-storefront.ui.button>
                    </form>
                    @if ($couponError)
                        <p class="text-error text-xs mb-4">{{ $couponError }}</p>
                    @endif

                    <div class="border-t border-border pt-4 mt-4">
                        <x-storefront.ui.button href="{{ route('storefront.checkout') }}" variant="accent" size="lg" class="w-full">
                            Proceed to Checkout
                        </x-storefront.ui.button>
                    </div>
                </x-storefront.ui.card>
            </div>
        </div>
    @endif
</div>
