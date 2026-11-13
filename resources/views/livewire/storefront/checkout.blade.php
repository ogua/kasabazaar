@php
    $items = $cart['items'] ?? [];
    $methods = collect($deliveryOptions['methods'] ?? []);
    $totalItems = (int) ($deliveryOptions['total_items'] ?? 0);
    $methodIcons = ['delivery' => 'truck', 'pickup' => 'map-pin', 'kasarose_shipping' => 'globe-alt'];
@endphp

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
                            <input type="radio" name="delivery-address" wire:model="selectedAddressId" @disabled($orderGroupId) value="{{ $address['id'] }}" class="mt-1 text-accent focus:ring-accent">
                            <div class="text-sm">
                                <p class="font-semibold text-fg">{{ $address['full_name'] }} &middot; {{ $address['phone'] }}</p>
                                <p class="text-muted">{{ collect([$address['street'] ?? null, $address['city'] ?? null, $address['region'] ?? null])->filter()->implode(', ') }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if ($showNewAddressForm)
                    <div class="border border-border rounded-lg p-4 mt-4">
                        <h3 class="font-semibold text-sm text-fg mb-3">New Address</h3>
                        <x-storefront.address-fields prefix="checkout-address" class="mb-4" />
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

            @if ($methods->isNotEmpty())
                <div>
                    <h2 class="font-display font-semibold text-lg text-navy-900 mb-1">Pickup and delivery options</h2>
                    <p class="text-sm text-muted mb-4">Choose how you want to receive your items.</p>

                    <div class="grid sm:grid-cols-3 gap-3" role="radiogroup" aria-label="Delivery method">
                        @foreach ($methods as $method)
                            @php
                                $covered = (int) $method['items_covered'];
                                $selectable = (bool) $method['selectable'];
                                $isSelected = $selectable && $deliveryMethod === $method['key'];
                                $availability = $covered === 0 ? 'No items in your cart' : ($covered === $totalItems ? 'Available for all items' : "Available for {$covered} of {$totalItems} items");
                            @endphp
                            <label @class([
                                'relative flex flex-col items-center text-center gap-1 rounded-lg border-2 p-4',
                                'cursor-pointer' => $selectable && ! $orderGroupId,
                                'focus-within:outline-2 focus-within:outline-accent' => $selectable,
                                'border-navy-900 bg-accent-soft/30' => $isSelected,
                                'border-border' => ! $isSelected,
                                'opacity-50' => $covered === 0,
                            ])>
                                @if ($selectable)
                                    <input type="radio" name="delivery-method" value="{{ $method['key'] }}" wire:model.live="deliveryMethod" @disabled($orderGroupId) class="sr-only">
                                @endif
                                <x-storefront.icon :name="$methodIcons[$method['key']] ?? 'truck'" class="w-8 h-8 text-navy-900" />
                                <span class="font-semibold text-fg">{{ $method['label'] }}</span>
                                <span class="text-xs text-muted">{{ $availability }}</span>
                                @if ($covered > 0)
                                    <span class="text-sm font-medium tabular-nums text-navy-900">{{ ($method['fee_mode'] ?? '') === 'free' ? 'Free' : 'Cost confirmed after you order' }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @if ($deliveryMethod === 'pickup')
                        <div class="mt-4 rounded-lg border border-border p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h3 class="font-semibold text-sm text-fg">Choose a pickup station</h3>
                                <div class="inline-flex rounded-sm border border-border overflow-hidden text-xs font-semibold" role="group" aria-label="Pickup country">
                                    @foreach (['Ghana', 'USA'] as $country)
                                        <button type="button" wire:click="$set('pickupCountry', '{{ $country }}')" @class(['px-3 py-1.5', 'bg-navy-900 text-white' => $pickupCountry === $country, 'text-fg hover:bg-surface-muted' => $pickupCountry !== $country])>{{ $country }}</button>
                                    @endforeach
                                </div>
                            </div>

                            @forelse ($pickupStations as $station)
                                <label class="flex items-start gap-3 border border-border rounded-lg p-3 mb-2 cursor-pointer has-checked:border-accent has-checked:bg-accent-soft/40" wire:key="station-{{ $station['id'] }}">
                                    <input type="radio" name="pickup-station" wire:model.live="pickupStationId" value="{{ $station['id'] }}" @disabled($orderGroupId) class="mt-1 text-accent focus:ring-accent">
                                    <div class="text-sm">
                                        <p class="font-semibold text-fg">{{ $station['name'] }}</p>
                                        <p class="text-muted">{{ $station['address'] }}</p>
                                        @if (! empty($station['opening_hours']))<p class="text-muted">{{ $station['opening_hours'] }}</p>@endif
                                        @if (! empty($station['phone']))<p class="text-muted">{{ $station['phone'] }}</p>@endif
                                        @if (! empty($station['instructions']))<p class="text-xs text-muted mt-1">{{ $station['instructions'] }}</p>@endif
                                    </div>
                                </label>
                            @empty
                                <p class="text-sm text-muted">There are no pickup stations in {{ $pickupCountry }} yet. Choose Delivery instead, or try the other country.</p>
                            @endforelse
                            <p class="text-xs text-muted mt-2">Your order is first checked at our warehouse, then we tell you when it is ready to collect.</p>
                        </div>
                    @endif

                    @php $kasarose = $methods->firstWhere('key', 'kasarose_shipping'); @endphp
                    @if ($deliveryMethod !== 'kasarose_shipping' && $totalItems > ($methods->firstWhere('key', 'delivery')['items_covered'] ?? $totalItems))
                        <p class="mt-3 text-sm text-muted">Items held in the USA are always shipped to Ghana by KASAROSE LOGISTICS and priced by weight.</p>
                    @endif
                    @if ($kasarose && $deliveryMethod === 'kasarose_shipping')
                        <p class="mt-3 text-sm text-muted">
                            Your order ({{ number_format($kasarose['weight_kg'] ?? 0, 2) }} kg) will be shipped from the USA to Ghana by KASAROSE LOGISTICS. {{ $kasarose['description'] }}
                        </p>
                    @endif
                </div>
            @endif

            <div>
                <label for="order-notes" class="block font-display font-semibold text-lg text-navy-900 mb-3">Order Notes (optional)</label>
                <textarea id="order-notes" wire:model="notes" maxlength="500" rows="3" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent" placeholder="Delivery instructions, gate code, etc."></textarea>
            </div>

            <label class="flex items-start gap-3 text-sm text-navy-900">
                <input type="checkbox" wire:model="whatsappOptIn" class="mt-0.5">
                <span>Send my order updates on WhatsApp. You can reply STOP at any time to switch back to SMS.</span>
            </label>
        </div>

        <div>
            <x-storefront.ui.card class="lg:sticky lg:top-32">
                <h2 class="font-display font-semibold text-lg text-navy-900 mb-4">Order Summary</h2>

                <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                    @foreach ($items as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">{{ $item['product']['name'] ?? 'Product' }} &times; {{ $item['quantity'] }}</span>
                            <span class="font-medium tabular-nums shrink-0 ml-2">GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</span>
                        </div>
                    @endforeach
                </div>

                @php
                    $subtotal = (float) ($cart['subtotal_ghs'] ?? 0);
                    $discount = (float) ($cart['discount_ghs'] ?? 0);
                    $memberDiscount = (float) ($cart['membership_discount_ghs'] ?? 0);
                    $shippingTotal = 0.0;
                    $deliveryQuoted = $methods->isNotEmpty() && (($methods->firstWhere('key', $deliveryMethod)['fee_mode'] ?? 'free') === 'quoted');
                @endphp
                <dl class="border-t border-border pt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Subtotal</dt>
                        <dd class="font-medium tabular-nums">GHS {{ number_format($subtotal, 2) }}</dd>
                    </div>
                    @if ($discount > 0)
                        <div class="flex justify-between text-success">
                            <dt>Discount @if (! empty($cart['coupon_code']))<span class="text-xs">({{ $cart['coupon_code'] }})</span>@endif</dt>
                            <dd class="font-medium tabular-nums">-GHS {{ number_format($discount, 2) }}</dd>
                        </div>
                    @endif
                    @if ($memberDiscount > 0)
                        <div class="flex justify-between text-success">
                            <dt>Member discount</dt>
                            <dd class="font-medium tabular-nums">-GHS {{ number_format($memberDiscount, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-muted">Delivery</dt>
                        <dd class="text-muted">@if ($methods->isEmpty()) Added per vendor @elseif ($deliveryQuoted) Quoted after you order @else Free @endif</dd>
                    </div>
                    <div class="flex justify-between border-t border-border pt-3">
                        <dt class="font-semibold text-fg">{{ $methods->isNotEmpty() && ! $deliveryQuoted ? 'Total' : 'Total before delivery' }}</dt>
                        <dd class="font-display font-semibold text-navy-900 tabular-nums">GHS {{ number_format(max(0, $subtotal - $discount - $memberDiscount) + ($methods->isNotEmpty() ? $shippingTotal : 0), 2) }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-muted mt-2 mb-4">Delivery and KASAROSE shipping costs are set by our shipping team after you order. You will be notified to pay them, and our delivery team then contacts you to plan the delivery. Any tax is added to your order total when you place the order.</p>

                @if ($error)
                    {{-- Repeated beside the button so it is visible on phones, where the top of the page is off-screen. --}}
                    <p class="lg:hidden text-error text-sm mb-3" role="alert">{{ $error }}</p>
                @endif

                <x-storefront.ui.button
                    variant="accent"
                    size="lg"
                    wire:click="placeOrder"
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="placeOrder" class="inline-flex items-center gap-2"><x-storefront.icon name="lock" class="w-4 h-4" /> {{ $orderGroupId ? 'Retry payment' : 'Place Order & Pay' }}</span>
                    <span wire:loading wire:target="placeOrder" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> {{ $orderGroupId ? 'Opening payment...' : 'Placing order...' }}</span>
                </x-storefront.ui.button>
                <p class="text-xs text-muted text-center mt-3">Mobile Money or card via Paystack, or card via Stripe.</p>
            </x-storefront.ui.card>
        </div>
    </div>
</div>
