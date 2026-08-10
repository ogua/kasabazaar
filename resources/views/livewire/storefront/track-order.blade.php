<div class="max-w-xl mx-auto px-4 sm:px-6 py-16">
    <div class="text-center mb-8">
        <h1 class="font-display font-bold text-2xl text-navy-900">Track Your Order</h1>
        <p class="text-muted text-sm mt-1">Enter your order number and the phone or email used at checkout.</p>
    </div>

    <x-storefront.ui.card>
        <form wire:submit.prevent="track" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-fg mb-1.5">Order Number</label>
                <input type="text" wire:model="orderNumber" required autofocus placeholder="KMB-20260810-0001" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                @error('orderNumber') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-fg mb-1.5">Phone or Email</label>
                <input type="text" wire:model="contact" required placeholder="Used at checkout" class="w-full border border-border rounded-sm px-3 py-2.5 text-sm focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                @error('contact') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <x-storefront.ui.button type="submit" variant="primary" size="lg" wire:loading.attr="disabled" wire:target="track" class="w-full">
                <span wire:loading.remove wire:target="track">Track Order</span>
                <span wire:loading wire:target="track" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Searching...</span>
            </x-storefront.ui.button>
        </form>
    </x-storefront.ui.card>

    @if ($searched)
        <div class="mt-8">
            @if ($error)
                <x-storefront.ui.alert variant="error">{{ $error }}</x-storefront.ui.alert>
            @elseif (! empty($order))
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="font-display font-semibold text-lg text-navy-900">Order {{ $order['order_number'] }}</h2>
                    <x-storefront.ui.badge>{{ ucfirst(str_replace('_', ' ', $order['status'])) }}</x-storefront.ui.badge>
                </div>

                @include('livewire.storefront.partials.order-status-timeline', ['order' => $order])
            @endif
        </div>
    @endif
</div>
