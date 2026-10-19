<div class="bg-surface-muted pb-14">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 pt-6">
        @if ($notWinner)
            <x-storefront.ui.empty-state
                icon="gavel"
                title="Nothing to pay here"
                description="Only the winning bidder can pay for an auction, and only after it has closed."
                class="bg-surface"
            >
                <x-slot:action>
                    <x-storefront.ui.button href="{{ route('storefront.auctions') }}" variant="primary">See auctions</x-storefront.ui.button>
                </x-slot:action>
            </x-storefront.ui.empty-state>
        @else
            @php
                $image = $auction['images'][0] ?? null;
                $dueAt = $auction['viewer']['payment_due_at'] ?? null;
            @endphp

            <x-storefront.ui.breadcrumb :items="[
                ['label' => 'Auctions', 'href' => route('storefront.auctions')],
                ['label' => $auction['title'], 'href' => route('storefront.auction', $auction['slug'])],
                ['label' => 'Pay'],
            ]" />

            @if ($error)
                <x-storefront.ui.alert variant="error" class="mb-4">{{ $error }}</x-storefront.ui.alert>
            @endif

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] items-start">
                <section class="bg-surface border border-border rounded-lg">
                    <h1 class="px-4 sm:px-6 h-14 flex items-center border-b border-border font-display font-bold text-xl text-navy-900">Delivery address</h1>
                    <div class="p-4 sm:p-6 space-y-3">
                        @foreach ($addresses as $address)
                            <label class="flex items-start gap-3 border border-border rounded-lg p-4 cursor-pointer has-checked:border-accent has-checked:bg-accent-soft/40" wire:key="address-{{ $address['id'] }}">
                                <input type="radio" wire:model="selectedAddressId" value="{{ $address['id'] }}" class="mt-1 accent-accent">
                                <span class="text-sm">
                                    <span class="block font-semibold text-fg">{{ $address['full_name'] }} &middot; {{ $address['phone'] }}</span>
                                    <span class="block text-muted">{{ collect([$address['street'] ?? null, $address['city'] ?? null, $address['region'] ?? null])->filter()->implode(', ') }}</span>
                                </span>
                            </label>
                        @endforeach

                        @if ($showNewAddressForm)
                            <div class="border border-border rounded-lg p-4">
                                <h2 class="font-semibold text-sm text-fg mb-3">New address</h2>
                                <x-storefront.address-fields prefix="pay-address" class="mb-4" />
                                <x-storefront.ui.button variant="primary" size="sm" wire:click="saveAddress" wire:loading.attr="disabled" wire:target="saveAddress">
                                    <span wire:loading.remove wire:target="saveAddress">Save address</span>
                                    <span wire:loading wire:target="saveAddress" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Saving…</span>
                                </x-storefront.ui.button>
                            </div>
                        @else
                            <button type="button" wire:click="$set('showNewAddressForm', true)" class="text-sm font-semibold text-navy-500 hover:text-accent">+ Add a new address</button>
                        @endif

                        <div class="pt-2">
                            <label for="pay-notes" class="block text-sm font-medium text-fg mb-1.5">Delivery notes (optional)</label>
                            <textarea id="pay-notes" wire:model="notes" rows="2" placeholder="Gate code, landmark, best time to call…" class="w-full border border-border rounded-sm px-3 py-2 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                        </div>
                    </div>
                </section>

                <aside class="lg:sticky lg:top-32 bg-surface border border-border rounded-lg">
                    <h2 class="px-4 h-12 flex items-center border-b border-border font-display font-semibold text-lg text-navy-900">Your win</h2>
                    <div class="p-4">
                        <div class="flex gap-3 mb-4">
                            <img src="{{ $image ?? asset('images/product-placeholder.png') }}" alt="" class="w-20 h-20 rounded-sm object-cover border border-border shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-fg line-clamp-2">{{ $auction['title'] }}</p>
                                @if (! empty($auction['vendor']['business_name']))
                                    <p class="text-xs text-muted mt-0.5 truncate">Sold by {{ $auction['vendor']['business_name'] }}</p>
                                @endif
                            </div>
                        </div>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted">Winning bid</dt>
                                <dd class="font-display font-bold text-navy-900 tabular-nums">GHS {{ number_format($auction['current_price_ghs'] ?? 0, 2) }}</dd>
                            </div>
                            <div class="flex justify-between text-muted">
                                <dt>Delivery &amp; tax</dt>
                                <dd>Added at payment</dd>
                            </div>
                        </dl>
                        @if ($dueAt)
                            <p class="mt-4 rounded-sm bg-warning/10 px-3 py-2 text-xs text-warning font-medium">
                                Pay by {{ \Illuminate\Support\Carbon::parse($dueAt)->timezone(config('app.timezone'))->format('D j M, g:i a') }} to secure this item.
                            </p>
                        @endif
                    </div>
                    <div class="p-4 border-t border-border">
                        <x-storefront.ui.button variant="accent" size="lg" class="w-full" wire:click="pay" wire:loading.attr="disabled" wire:target="pay">
                            <span wire:loading.remove wire:target="pay" class="inline-flex items-center gap-2"><x-storefront.icon name="lock" class="w-4 h-4" /> Continue to payment</span>
                            <span wire:loading wire:target="pay" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Starting payment…</span>
                        </x-storefront.ui.button>
                        <p class="mt-2 text-xs text-muted text-center">Mobile Money or card via Paystack, or card via Stripe.</p>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>
