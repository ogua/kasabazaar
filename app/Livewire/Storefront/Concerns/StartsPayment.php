<?php

namespace App\Livewire\Storefront\Concerns;

use App\Services\Kasabazaar\CheckoutApi;
use Illuminate\Support\Facades\Log;

/**
 * Hands an order group to the payment gateway the backend picks for it.
 * Shared by cart checkout, auction checkout and "Complete payment" on an
 * unpaid order, so all three follow the same Paystack / Stripe hand-off.
 */
trait StartsPayment
{
    /**
     * Redirects to the gateway, or returns the message to show when the
     * backend's response could not be used. KasabazaarApiException from
     * `payments/initiate` is left for the caller to handle.
     */
    protected function startPayment(CheckoutApi $checkoutApi, string $orderGroupId): ?string
    {
        $payment = $checkoutApi->initiatePayment($orderGroupId);
        $gateway = $payment['gateway'] ?? null;

        if ($gateway === 'paystack' && ! empty($payment['authorization_url'])) {
            $this->redirect($payment['authorization_url'], navigate: false);

            return null;
        }

        if ($gateway === 'stripe' && ! empty($payment['client_secret'])) {
            // The callback page mounts Stripe.js Elements with the client secret
            // and confirms the payment client-side.
            $this->redirect(route('storefront.checkout.callback', [
                'gateway' => 'stripe',
                'client_secret' => $payment['client_secret'],
                'order_group_id' => $orderGroupId,
            ]), navigate: false);

            return null;
        }

        Log::warning('storefront.payment: unrecognized payment gateway response', [
            // Keys only: the payload can carry client secrets that must not reach the logs.
            'gateway' => $gateway,
            'keys' => array_keys($payment),
        ]);

        return 'We could not start the payment process. Please try again or contact support.';
    }
}
