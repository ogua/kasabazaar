@if ($notFound)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-20">
        <x-storefront.ui.empty-state icon="box" title="Order not found" description="This order may not exist or you don't have access to it.">
            <x-slot:action>
                <x-storefront.ui.button href="{{ route('storefront.account.orders') }}" variant="primary">Back to Orders</x-storefront.ui.button>
            </x-slot:action>
        </x-storefront.ui.empty-state>
    </div>
@else
    @php $cancellable = in_array($order['status'], ['pending', 'awaiting_payment']); @endphp

    <x-storefront.account-layout :title="'Order '.$order['order_number']">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <x-storefront.ui.badge>{{ ucfirst($order['status']) }}</x-storefront.ui.badge>
                <span class="text-sm text-muted">Sold by {{ $order['vendor']['business_name'] ?? '—' }}</span>
            </div>
            @if ($cancellable)
                <button
                    type="button"
                    wire:click="cancel"
                    wire:confirm="Cancel this order?"
                    wire:loading.attr="disabled"
                    wire:target="cancel"
                    class="inline-flex items-center gap-2 text-sm font-medium text-error border border-error rounded-sm px-3 py-1.5 hover:bg-error hover:text-white disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="cancel">Cancel Order</span>
                    <span wire:loading wire:target="cancel" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Cancelling...</span>
                </button>
            @endif
        </div>

        @include('livewire.storefront.partials.order-status-timeline', ['order' => $order])

        <div class="border border-border rounded-lg overflow-hidden mb-6">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">Product</th>
                        <th class="text-center px-4 py-3 font-semibold">Qty</th>
                        <th class="text-right px-4 py-3 font-semibold">Unit Price</th>
                        <th class="text-right px-4 py-3 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($order['items'] ?? [] as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-fg">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ $item['quantity'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">GHS {{ number_format($item['unit_price_ghs'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">GHS {{ number_format($item['total_ghs'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid sm:grid-cols-2 gap-6 mb-6">
            <x-storefront.ui.card>
                <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Order Total</h2>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Subtotal</span><span class="tabular-nums">GHS {{ number_format($order['subtotal_ghs'] ?? 0, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Shipping</span><span class="tabular-nums">GHS {{ number_format($order['shipping_fee_ghs'] ?? 0, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Tax</span><span class="tabular-nums">GHS {{ number_format($order['tax_ghs'] ?? 0, 2) }}</span></div>
                    @if (($order['discount_ghs'] ?? 0) > 0)
                        <div class="flex justify-between text-success"><span>Discount</span><span class="tabular-nums">-GHS {{ number_format($order['discount_ghs'], 2) }}</span></div>
                    @endif
                    <div class="flex justify-between font-semibold text-navy-900 pt-1.5 border-t border-border"><span>Total</span><span class="tabular-nums">GHS {{ number_format($order['total_ghs'] ?? 0, 2) }}</span></div>
                </div>
            </x-storefront.ui.card>

            @if (! empty($order['delivery_detail']))
                <x-storefront.ui.card>
                    <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Delivery Address</h2>
                    <p class="text-sm text-fg">{{ $order['delivery_detail']['full_name'] }} &middot; {{ $order['delivery_detail']['phone'] }}</p>
                    <p class="text-sm text-muted mt-1">{{ $order['delivery_detail']['street'] }}, {{ $order['delivery_detail']['city'] }}, {{ $order['delivery_detail']['region'] }}</p>
                </x-storefront.ui.card>
            @endif
        </div>

        @if ($order['status'] === 'delivered' && empty($order['rating']))
            <x-storefront.ui.card>
                @if ($rated)
                    <p class="text-success text-sm font-medium">Thanks for rating this order!</p>
                @else
                    <h2 class="font-display font-semibold text-sm text-navy-900 uppercase tracking-wide mb-3">Rate this order</h2>
                    <div class="flex items-center gap-3 mb-3">
                        <select wire:model="ratingValue" class="border border-border rounded-sm px-3 py-2 text-sm max-w-28 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent">
                            <option value="5">5 stars</option>
                            <option value="4">4 stars</option>
                            <option value="3">3 stars</option>
                            <option value="2">2 stars</option>
                            <option value="1">1 star</option>
                        </select>
                    </div>
                    <textarea wire:model="ratingComment" rows="2" placeholder="Comments (optional)" class="w-full border border-border rounded-sm px-3 py-2 text-sm mb-3 focus:outline-none focus-visible:outline-2 focus-visible:outline-accent"></textarea>
                    <x-storefront.ui.button variant="primary" size="sm" wire:click="submitRating" wire:loading.attr="disabled" wire:target="submitRating">
                        <span wire:loading.remove wire:target="submitRating">Submit Rating</span>
                        <span wire:loading wire:target="submitRating" class="inline-flex items-center gap-2"><x-storefront.ui.spinner class="w-4 h-4" /> Submitting...</span>
                    </x-storefront.ui.button>
                @endif
            </x-storefront.ui.card>
        @endif
    </x-storefront.account-layout>
@endif
