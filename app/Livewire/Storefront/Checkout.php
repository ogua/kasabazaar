<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
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
        $this->cart = $cartApi->show();
        $this->addresses = $checkoutApi->addresses();

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

            if (($payment['gateway'] ?? null) === 'paystack' && ! empty($payment['authorization_url'])) {
                $this->redirect($payment['authorization_url'], navigate: false);

                return;
            }

            // Stripe: hand off to the callback page with the client secret so it
            // can mount Stripe.js Elements and confirm the payment client-side.
            $this->redirect(route('storefront.checkout.callback', [
                'gateway' => 'stripe',
                'client_secret' => $payment['client_secret'] ?? null,
                'order_group_id' => $orderGroup['id'],
            ]), navigate: false);
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
