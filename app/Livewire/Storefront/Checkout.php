<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\StartsPayment;
use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Checkout extends Component
{
    use StartsPayment;

    public array $cart = [];

    public array $addresses = [];

    public string $selectedAddressId = '';

    public string $notes = '';

    public bool $whatsappOptIn = false;

    public string $error = '';

    /** Set once checkout has created the order, so a retry only restarts payment. */
    #[Locked]
    public ?string $orderGroupId = null;

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

        // Nothing to buy: send the shopper back to the cart, which explains why.
        if (! $this->error && empty($this->cart['items'] ?? [])) {
            $this->redirect(route('storefront.cart'), navigate: false);

            return;
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
            'region' => ['required', 'string', Rule::in(config('group.regions'))],
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
        // Once the backend has turned the cart into an order, a retry only
        // restarts payment: checkout empties the cart, so a second checkout
        // call would fail with "cart is empty".
        if (! $this->orderGroupId) {
            if (empty($this->cart['items'] ?? [])) {
                $this->error = 'Your cart is empty. Add something before checking out.';

                return;
            }

            if (! $this->selectedAddressId) {
                $this->error = 'Please select or add a delivery address.';

                return;
            }

            try {
                $orderGroup = $checkoutApi->checkout($this->selectedAddressId, $this->notes, $this->whatsappOptIn);
            } catch (KasabazaarApiException $e) {
                $this->error = $e->getMessage();

                return;
            }

            $this->orderGroupId = $orderGroup['id'];
            $this->dispatch('cart-updated');
        }

        try {
            $failure = $this->startPayment($checkoutApi, $this->orderGroupId);
        } catch (KasabazaarApiException $e) {
            $failure = $e->getMessage();
        }

        if ($failure) {
            $this->error = 'Your order is saved, but we could not start payment: '.$failure.' Press "Retry payment" to try again, or pay later from My Orders.';
        }
    }

    public function render()
    {
        return view('livewire.storefront.checkout')->layout('storefront.layouts.app', ['title' => 'Checkout']);
    }
}
