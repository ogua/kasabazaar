<div class="max-w-lg mx-auto px-4 sm:px-6 py-20 text-center">
    @if ($status === 'verifying')
        <div class="flex justify-center mb-5">
            <x-storefront.ui.spinner class="w-10 h-10 text-navy-900" />
        </div>
        <h1 class="font-display font-bold text-2xl text-navy-900 mb-2">Verifying your payment&hellip;</h1>
        <p class="text-muted">Please wait a moment, this won't take long.</p>
    @elseif ($status === 'success')
        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-success/10 text-success mx-auto mb-5">
            <x-storefront.icon name="check-circle" class="w-8 h-8" />
        </div>
        <h1 class="font-display font-bold text-2xl text-navy-900 mb-2">Payment Confirmed!</h1>
        <p class="text-muted mb-6">Your order has been placed successfully.</p>

        @if (! empty($placedOrders))
            <div class="text-left border border-border rounded-lg divide-y divide-border mb-6">
                @foreach ($placedOrders as $placedOrder)
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <span class="text-sm font-medium text-fg">{{ $placedOrder['order_number'] }}</span>
                        <a href="{{ route('storefront.track-order', ['order_number' => $placedOrder['order_number']]) }}" class="text-sm text-navy-500 hover:text-accent font-medium">Track this order</a>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($orderGroupId)
            <x-storefront.ui.button href="{{ route('storefront.account.orders') }}" variant="primary">View My Orders</x-storefront.ui.button>
        @endif
    @elseif ($status === 'awaiting-stripe')
        <div wire:ignore id="stripe-payment-root" data-client-secret="{{ $stripeClientSecret }}" data-order-group-id="{{ $orderGroupId }}" class="text-left">
            <h1 class="font-display font-bold text-2xl text-navy-900 mb-5 text-center">Complete Your Payment</h1>
            <div id="stripe-payment-element" class="mb-4"></div>
            <button id="stripe-submit" class="w-full inline-flex items-center justify-center gap-2 rounded-sm bg-accent text-white font-semibold py-3 hover:bg-accent-hover disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="stripe-submit-label">Pay Now</span>
            </button>
            <p id="stripe-payment-message" class="text-error text-sm mt-3 text-center"></p>
        </div>
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var root = document.getElementById('stripe-payment-root');
                if (!root || !window.Stripe) return;
                var stripe = Stripe('{{ config('services.stripe.publishable_key') }}');
                var elements = stripe.elements({ clientSecret: root.dataset.clientSecret });
                elements.create('payment').mount('#stripe-payment-element');

                var submitButton = document.getElementById('stripe-submit');
                var submitLabel = document.getElementById('stripe-submit-label');

                submitButton.addEventListener('click', function () {
                    submitButton.disabled = true;
                    submitLabel.textContent = 'Processing...';

                    stripe.confirmPayment({ elements, redirect: 'if_required' }).then(function (result) {
                        if (result.error) {
                            document.getElementById('stripe-payment-message').textContent = result.error.message;
                            submitButton.disabled = false;
                            submitLabel.textContent = 'Pay Now';
                        } else {
                            window.Livewire.dispatch('stripe-confirmed', { paymentIntentId: result.paymentIntent.id });
                        }
                    });
                });
            });
        </script>
    @else
        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 text-error mx-auto mb-5">
            <x-storefront.icon name="exclamation-circle" class="w-8 h-8" />
        </div>
        <h1 class="font-display font-bold text-2xl text-navy-900 mb-2">Payment Failed</h1>
        <p class="text-muted mb-6">{{ $message }}</p>
        <x-storefront.ui.button href="{{ route('storefront.checkout') }}" variant="primary">Try Again</x-storefront.ui.button>
    @endif
</div>
