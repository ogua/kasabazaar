@php $items = $cart['items'] ?? []; @endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="font-display font-bold text-2xl text-navy-900 mb-8">Checkout</h1>

    @if ($error)
        <x-storefront.ui.alert variant="error" class="mb-8">{{ $error }}</x-storefront.ui.alert>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            <div>
                <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Delivery Address</h2>

                <div class="space-y-3">
                    @foreach ($addresses as $address)
                        <label class="flex items-start gap-3 border border-border rounded-lg p-4 cursor-pointer has-checked:border-accent has-checked:bg-accent-soft/40">
                            <input type="radio" wire:model="selectedAddressId" value="{{ $address['id'] }}" class="mt-1 text-accent focus:ring-accent">
                            <div class="text-sm">
                                <p class="font-semibold text-fg">{{ $address['full_name'] }} &middot; {{ $address['phone'] }}</p>
                                <p class="text-muted">{{ $address['street'] }}, {{ $address['city'] }}, {{ $address['region'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if ($showNewAddressForm)
                    <div class="border border-border rounded-lg p-4 mt-4">
                        <h3 class="font-semibold text-sm text-fg mb-3">New Address</h3>
                        <div class="grid sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <input class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Full name" wire:model="full_name">
                                @error('full_name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Phone" wire:model="phone">
                                @error('phone') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Region (e.g. Greater Accra)" wire:model="region">
                                @error('region') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="City" wire:model="city">
                                @error('city') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <input class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Street / landmark" wire:model="street">
                            </div>
                        </div>
                        <x-storefront.ui.button variant="primary" size="sm" wire:click="saveAddress" wire:loading.attr="disabled" wire:target="saveAddress">
                            <span wire:loading.remove wire:target="saveAddress">Save Address</span>
                            <span wire:loading wire:target="saveAddress" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Saving...</span>
                        </x-storefront.ui.button>
                    </div>
                @else
                    <button type="button" wire:click="$set('showNewAddressForm', true)" class="mt-4 text-sm font-medium text-navy-500 hover:text-accent">
                        + Add a new address
                    </button>
                @endif
            </div>

            <div>
                <label class="block font-display font-semibold text-lg text-navy-900 mb-3">Order Notes (optional)</label>
                <textarea wire:model="notes" rows="3" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Delivery instructions, gate code, etc."></textarea>
            </div>
        </div>

        <div>
            <x-storefront.ui.card class="sticky top-16">
                <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Order Summary</h2>

                <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                    @foreach ($items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">{{ $item['product']['name'] ?? 'Product' }} &times; {{ $item['quantity'] }}</span>
                            <span class="font-medium tabular-nums shrink-0 ml-2">GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-border pt-4 flex justify-between">
                    <span class="font-semibold text-fg">Subtotal</span>
                    <span class="font-display font-semibold text-navy-900 tabular-nums">GHS {{ number_format($cart['subtotal_ghs'] ?? 0, 2) }}</span>
                </div>
                <p class="text-xs text-muted mt-2 mb-4">Shipping and tax are calculated per vendor and shown on your order confirmation.</p>

                <x-storefront.ui.button
                    variant="accent"
                    size="lg"
                    wire:click="placeOrder"
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="placeOrder">Place Order &amp; Pay</span>
                    <span wire:loading wire:target="placeOrder" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Placing order...</span>
                </x-storefront.ui.button>
            </x-storefront.ui.card>
        </div>
    </div>
</div>
