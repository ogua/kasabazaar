<div class="container py-8 text-center" style="max-width:560px;">
    @if ($status === 'verifying')
        <h1>Verifying your payment…</h1>
        <p>Please wait a moment.</p>
    @elseif ($status === 'success')
        <h1 class="text-success">Payment Confirmed!</h1>
        <p>Your order has been placed successfully.</p>
        @if ($orderGroupId)
            <a href="{{ route('storefront.account.orders') }}" class="btn btn-dark btn-rounded mt-4">View My Orders</a>
        @endif
    @elseif ($status === 'awaiting-stripe')
        <div wire:ignore id="stripe-payment-root" data-client-secret="{{ $stripeClientSecret }}" data-order-group-id="{{ $orderGroupId }}">
            <h1>Complete Your Payment</h1>
            <div id="stripe-payment-element" class="my-4"></div>
            <button id="stripe-submit" class="btn btn-primary btn-rounded">Pay Now</button>
            <div id="stripe-payment-message" class="text-danger mt-2"></div>
        </div>
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var root = document.getElementById('stripe-payment-root');
                if (!root || !window.Stripe) return;
                var stripe = Stripe('{{ config('services.stripe.publishable_key') }}');
                var elements = stripe.elements({ clientSecret: root.dataset.clientSecret });
                elements.create('payment').mount('#stripe-payment-element');

                document.getElementById('stripe-submit').addEventListener('click', function () {
                    stripe.confirmPayment({ elements, redirect: 'if_required' }).then(function (result) {
                        if (result.error) {
                            document.getElementById('stripe-payment-message').textContent = result.error.message;
                        } else {
                            window.Livewire.dispatch('stripe-confirmed', { paymentIntentId: result.paymentIntent.id });
                        }
                    });
                });
            });
        </script>
    @else
        <h1 class="text-danger">Payment Failed</h1>
        <p>{{ $message }}</p>
        <a href="{{ route('storefront.checkout') }}" class="btn btn-dark btn-rounded mt-4">Try Again</a>
    @endif
</div>
