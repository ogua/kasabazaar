<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Checkout extends Component
{
    public array $cart = [];

    public array $addresses = [];

    public string $selectedAddressId = '';

    public string $notes = '';

    public string $error = '';

    public bool $placingOrder = false;

    // New address form
    public bool $showNewAddressForm = false;

    public string $full_name = '';

    public string $phone = '';

    public string $region = '';

    public string $city = '';

    public string $street = '';

    public function mount(CartApi $cartApi, CheckoutApi $checkoutApi): void
    {
        try {
            $this->cart = $cartApi->show();
            $this->addresses = $checkoutApi->addresses();
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.checkout: failed to load checkout data', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your checkout right now. Please refresh or try again shortly.';
        }

        $default = collect($this->addresses)->firstWhere('is_default', true) ?? ($this->addresses[0] ?? null);
        $this->selectedAddressId = $default['id'] ?? '';
        $this->showNewAddressForm = empty($this->addresses);
    }

    public function saveAddress(CheckoutApi $checkoutApi): void
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'region' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'street' => 'nullable|string|max:255',
        ]);

        try {
            $address = $checkoutApi->createAddress([
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'country' => 'Ghana',
                'region' => $this->region,
                'city' => $this->city,
                'street' => $this->street,
                'is_default' => empty($this->addresses),
            ]);

            $this->addresses[] = $address;
            $this->selectedAddressId = $address['id'];
            $this->showNewAddressForm = false;
            $this->error = '';
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function placeOrder(CheckoutApi $checkoutApi): void
    {
        if (! $this->selectedAddressId) {
            $this->error = 'Please select or add a delivery address.';

            return;
        }

        $this->placingOrder = true;

        try {
            $orderGroup = $checkoutApi->checkout($this->selectedAddressId, $this->notes);
            $payment = $checkoutApi->initiatePayment($orderGroup['id']);
            $gateway = $payment['gateway'] ?? null;

            if ($gateway === 'paystack' && ! empty($payment['authorization_url'])) {
                $this->redirect($payment['authorization_url'], navigate: false);

                return;
            }

            if ($gateway === 'stripe' && ! empty($payment['client_secret'])) {
                // Hand off to the callback page with the client secret so it can
                // mount Stripe.js Elements and confirm the payment client-side.
                $this->redirect(route('storefront.checkout.callback', [
                    'gateway' => 'stripe',
                    'client_secret' => $payment['client_secret'],
                    'order_group_id' => $orderGroup['id'],
                ]), navigate: false);

                return;
            }

            Log::warning('storefront.checkout: unrecognized payment gateway response', ['payment' => $payment]);
            $this->error = 'We could not start the payment process. Please try again or contact support.';
            $this->placingOrder = false;
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
            $this->placingOrder = false;
        }
    }

    public function render()
    {
        return view('livewire.storefront.checkout')->layout('storefront.layouts.app', ['title' => 'Checkout']);
    }
}
