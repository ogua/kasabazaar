@php
    $items = $cart['items'] ?? [];
    $itemCount = (int) collect($items)->sum('quantity');
    $problems = $this->problems;
    $subtotal = (float) ($cart['subtotal_ghs'] ?? 0);
    $discount = (float) ($cart['discount_ghs'] ?? 0);
@endphp

<div class="bg-surface-muted pb-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-6">
        <x-storefront.ui.breadcrumb :items="[['label' => 'Cart']]" />

        @if ($error)
            <x-storefront.ui.alert variant="error" class="mb-4">{{ $error }}</x-storefront.ui.alert>
        @endif

        @if (empty($items))
            @unless ($error)
                <x-storefront.ui.empty-state icon="cart" title="Your cart is empty" description="Looks like you haven't added anything yet." class="bg-surface">
                    <x-slot:action>
                        <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="primary">Continue Shopping</x-storefront.ui.button>
                    </x-slot:action>
                </x-storefront.ui.empty-state>
            @endunless
        @else
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] items-start">
                <section class="bg-surface border border-border rounded-lg" wire:loading.class="opacity-50" wire:target="updateQuantity,removeItem,removeCoupon">
                    <h1 class="flex items-baseline gap-2 px-4 sm:px-6 h-14 border-b border-border font-display font-bold text-xl text-navy-900">
                        <span class="self-center">Shopping Cart</span>
                        <span class="self-center text-sm font-sans font-normal text-muted tabular-nums">({{ $itemCount }} {{ Str::plural('item', $itemCount) }})</span>
                    </h1>

                    <ul class="divide-y divide-border">
                        @foreach ($items as $item)
                            @php
                                $itemProduct = $item['product'] ?? [];
                                $itemImage = $itemProduct['images'][0]['url'] ?? $itemProduct['primary_image_url'] ?? null;
                                $itemProductId = $itemProduct['id'] ?? $item['product_id'] ?? null;
                            @endphp
                            <li wire:key="cart-item-{{ $item['id'] }}" class="flex gap-4 px-4 sm:px-6 py-4">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-sm bg-surface-muted overflow-hidden border border-border">
                                    <img
                                        src="{{ $itemImage ?? asset('images/product-placeholder.png') }}"
                                        alt=""
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                        onerror="if (! this.src.endsWith('/images/product-placeholder.png')) this.src='{{ asset('images/product-placeholder.png') }}';"
                                    >
                                </div>

                                <div class="flex-1 min-w-0 grid sm:grid-cols-[minmax(0,1fr)_auto_auto] gap-x-6 gap-y-3 sm:items-center">
                                    <div class="min-w-0">
                                        @if ($itemProductId)
                                            <a href="{{ route('storefront.product', $itemProductId) }}" class="text-sm font-medium text-fg hover:text-accent line-clamp-2">{{ $itemProduct['name'] ?? 'Product' }}</a>
                                        @else
                                            <p class="text-sm font-medium text-fg line-clamp-2">{{ $itemProduct['name'] ?? 'Product' }}</p>
                                        @endif
                                        @if (! empty($item['vendor']['business_name']))
                                            <p class="text-xs text-muted mt-0.5 truncate">Sold by {{ $item['vendor']['business_name'] }}</p>
                                        @endif
                                        <p class="text-xs text-muted mt-1 tabular-nums">GHS {{ number_format($item['price_ghs'], 2) }} each</p>
                                        @if (isset($problems[$item['id']]))
                                            <p class="flex items-center gap-1 mt-1.5 text-xs font-medium text-error">
                                                <x-storefront.icon name="exclamation-circle" class="w-4 h-4 shrink-0" />
                                                {{ $problems[$item['id']] }}
                                            </p>
                                        @elseif (isset($item['stock']) && (int) $item['stock'] <= 5)
                                            <p class="mt-1.5 text-xs font-medium text-warning">Only {{ (int) $item['stock'] }} left</p>
                                        @endif
                                    </div>

                                    @php $limit = $this->quantityLimit($item); @endphp
                                    <div class="flex items-center gap-3">
                                        <label for="qty-{{ $item['id'] }}" class="sr-only">Quantity of {{ $itemProduct['name'] ?? 'item' }}</label>
                                        <div class="flex items-center border border-border rounded-sm" wire:key="qty-{{ $item['id'] }}-{{ $item['quantity'] }}-{{ $quantityRevision }}">
                                            <button
                                                type="button"
                                                wire:click="updateQuantity('{{ $item['id'] }}', {{ $item['quantity'] - 1 }})"
                                                @disabled($item['quantity'] <= 1)
                                                class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted disabled:opacity-40"
                                                aria-label="Decrease quantity of {{ $itemProduct['name'] ?? 'item' }}"
                                            >
                                                <x-storefront.icon name="minus" class="w-4 h-4" />
                                            </button>
                                            <input
                                                id="qty-{{ $item['id'] }}"
                                                type="number"
                                                inputmode="numeric"
                                                min="1"
                                                max="{{ $limit }}"
                                                value="{{ $item['quantity'] }}"
                                                wire:change="updateQuantity('{{ $item['id'] }}', $event.target.value)"
                                                class="w-12 h-10 border-x border-border text-center text-sm tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"
                                            >
                                            <button
                                                type="button"
                                                wire:click="updateQuantity('{{ $item['id'] }}', {{ $item['quantity'] + 1 }})"
                                                @disabled($item['quantity'] >= $limit)
                                                class="w-10 h-10 flex items-center justify-center text-navy-900 hover:bg-surface-muted disabled:opacity-40"
                                                aria-label="Increase quantity of {{ $itemProduct['name'] ?? 'item' }}"
                                            >
                                                <x-storefront.icon name="plus" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <button type="button" wire:click="removeItem('{{ $item['id'] }}')" wire:loading.attr="disabled" wire:target="removeItem('{{ $item['id'] }}')" class="sm:hidden inline-flex items-center gap-1 min-h-10 px-1 text-xs text-muted hover:text-error">
                                            <x-storefront.icon name="trash" class="w-4 h-4" /> Remove
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 sm:min-w-36">
                                        <span class="font-display font-bold text-navy-900 tabular-nums">GHS {{ number_format($item['price_ghs'] * $item['quantity'], 2) }}</span>
                                        <button type="button" wire:click="removeItem('{{ $item['id'] }}')" wire:loading.attr="disabled" wire:target="removeItem('{{ $item['id'] }}')" class="hidden sm:flex items-center justify-center w-8 h-8 rounded-sm text-muted hover:text-error hover:bg-surface-muted" aria-label="Remove {{ $itemProduct['name'] ?? 'item' }}">
                                            <x-storefront.icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="px-4 sm:px-6 py-3 border-t border-border">
                        <x-storefront.ui.button href="{{ route('storefront.shop') }}" variant="tertiary" size="sm" class="px-0!">
                            <x-storefront.icon name="chevron-left" class="w-4 h-4" /> Continue Shopping
                        </x-storefront.ui.button>
                    </div>
                </section>

                <aside class="space-y-4 lg:sticky lg:top-32">
                    <div class="bg-surface border border-border rounded-lg">
                        <h2 class="px-4 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">Order Summary</h2>
                        <div class="p-4">
                            <dl class="space-y-2 text-sm mb-4">
                                <div class="flex justify-between">
                                    <dt class="text-muted">Subtotal ({{ $itemCount }} {{ Str::plural('item', $itemCount) }})</dt>
                                    <dd class="font-medium tabular-nums">GHS {{ number_format($cart['subtotal_ghs'] ?? 0, 2) }}</dd>
                                </div>
                                @if (! empty($cart['coupon_code']))
                                    <div class="flex justify-between gap-3 text-success">
                                        <dt class="flex flex-wrap items-center gap-x-2">
                                            Coupon <span class="font-semibold">{{ $cart['coupon_code'] }}</span>
                                            <button type="button" wire:click="removeCoupon" wire:loading.attr="disabled" wire:target="removeCoupon" class="text-xs font-medium text-muted underline underline-offset-2 hover:text-error">Remove</button>
                                        </dt>
                                        <dd class="font-medium tabular-nums">-GHS {{ number_format($discount, 2) }}</dd>
                                    </div>
                                @endif
                                <div class="flex justify-between text-muted">
                                    <dt>Delivery</dt>
                                    <dd>Added per vendor at checkout</dd>
                                </div>
                                <div class="flex justify-between border-t border-border pt-2">
                                    <dt class="font-semibold text-fg">Total before delivery</dt>
                                    <dd class="font-display font-semibold text-navy-900 tabular-nums">GHS {{ number_format(max(0, $subtotal - $discount), 2) }}</dd>
                                </div>
                            </dl>

                            @if (empty($cart['coupon_code']))
                                @auth
                                    <form wire:submit.prevent="applyCoupon" class="flex gap-2">
                                        <label for="coupon-code" class="sr-only">Coupon code</label>
                                        <input id="coupon-code" type="text" wire:model="couponCode" placeholder="Coupon code" autocomplete="off" autocapitalize="characters" @if ($couponError) aria-invalid="true" aria-describedby="coupon-error" @endif class="flex-1 min-w-0 border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                                        <x-storefront.ui.button type="submit" variant="secondary" size="sm" wire:loading.attr="disabled" wire:target="applyCoupon">
                                            <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                            <x-storefront.ui.spinner class="w-4 h-4" wire:loading wire:target="applyCoupon" />
                                        </x-storefront.ui.button>
                                    </form>
                                    @if ($couponError)
                                        <p id="coupon-error" class="text-error text-xs mt-2" role="alert">{{ $couponError }}</p>
                                    @endif
                                @else
                                    {{-- The backend only applies coupons to signed-in shoppers. --}}
                                    <p class="text-sm text-muted">Have a coupon? <a href="{{ route('storefront.login') }}" class="font-semibold text-navy-500 hover:text-accent">Sign in</a> to use it. Your cart comes with you.</p>
                                @endif
                            @endif
                        </div>

                        <div class="p-4 border-t border-border">
                            @if (count($problems))
                                <p class="text-sm text-error mb-3" role="alert">{{ count($problems) === 1 ? 'One item needs' : count($problems).' items need' }} your attention before checkout.</p>
                                <x-storefront.ui.button variant="accent" size="lg" class="w-full" disabled>
                                    <x-storefront.icon name="lock" class="w-4 h-4" />
                                    Proceed to Checkout
                                </x-storefront.ui.button>
                            @else
                                <x-storefront.ui.button href="{{ route('storefront.checkout') }}" variant="accent" size="lg" class="w-full">
                                    <x-storefront.icon name="lock" class="w-4 h-4" />
                                    Proceed to Checkout
                                </x-storefront.ui.button>
                            @endif
                        </div>
                    </div>

                    <ul class="bg-surface border border-border rounded-lg divide-y divide-border text-sm">
                        <li class="flex items-start gap-3 p-4">
                            <x-storefront.icon name="lock" class="w-5 h-5 text-navy-500 shrink-0" />
                            <span><span class="font-semibold text-fg">Secure checkout.</span> <span class="text-muted">Mobile Money or card via Paystack, or card via Stripe.</span></span>
                        </li>
                        <li class="flex items-start gap-3 p-4">
                            <x-storefront.icon name="arrow-path" class="w-5 h-5 text-navy-500 shrink-0" />
                            <span><span class="font-semibold text-fg">{{ config('group.returns.window_days') }}-day returns</span> <span class="text-muted">on faulty or wrong items.</span> <a href="{{ route('storefront.returns') }}" class="text-navy-500 hover:text-accent">Details</a></span>
                        </li>
                    </ul>
                </aside>
            </div>
        @endif
    </div>
</div>
