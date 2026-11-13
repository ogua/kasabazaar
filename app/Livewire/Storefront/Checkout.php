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

    /** The buyer's choice: 'delivery', 'pickup' or 'kasarose_shipping'. Items held in the USA always go with KASAROSE. */
    public string $deliveryMethod = 'delivery';

    /** The pickup station chosen when the method is 'pickup'. */
    public string $pickupStationId = '';

    /** Which country's pickup stations are listed: 'Ghana' or 'USA'. */
    public string $pickupCountry = 'Ghana';

    /** @var array<int, array<string, mixed>> */
    public array $pickupStations = [];

    /** @var array{total_items?: int, methods?: array<int, array<string, mixed>>} */
    public array $deliveryOptions = [];

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

        // Nothing to buy, or a line checkout would reject (sold out, delisted,
        // over stock): send the shopper back to the cart, which explains why.
        if (! $this->error && (empty($this->cart['items'] ?? []) || CartPage::problemsIn($this->cart))) {
            $this->redirect(route('storefront.cart'), navigate: false);

            return;
        }

        $default = collect($this->addresses)->firstWhere('is_default', true) ?? ($this->addresses[0] ?? null);
        $this->selectedAddressId = $default['id'] ?? '';
        $this->showNewAddressForm = empty($this->addresses);

        $this->loadDeliveryOptions($checkoutApi);

        if ($this->deliveryMethod === 'pickup') {
            $this->loadPickupStations($checkoutApi);
        }
    }

    public function updatedDeliveryMethod(CheckoutApi $checkoutApi): void
    {
        if ($this->deliveryMethod === 'pickup') {
            $this->loadPickupStations($checkoutApi);
        }
    }

    public function updatedPickupCountry(CheckoutApi $checkoutApi): void
    {
        $this->pickupStationId = '';
        $this->loadPickupStations($checkoutApi);
    }

    protected function loadPickupStations(CheckoutApi $checkoutApi): void
    {
        try {
            $this->pickupStations = $checkoutApi->pickupStations($this->pickupCountry);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.checkout: failed to load pickup stations', ['message' => $e->getMessage()]);
            $this->pickupStations = [];
        }
    }

    public function updatedSelectedAddressId(): void
    {
        $this->loadDeliveryOptions(app(CheckoutApi::class));
    }

    /**
     * A failed lookup only hides the delivery cards: the backend still prices the order itself.
     */
    protected function loadDeliveryOptions(CheckoutApi $checkoutApi): void
    {
        $this->deliveryOptions = [];

        if (! $this->selectedAddressId) {
            return;
        }

        try {
            $this->deliveryOptions = $checkoutApi->deliveryOptions($this->selectedAddressId);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.checkout: failed to load delivery options', ['message' => $e->getMessage()]);
        }

        $local = collect($this->deliveryOptions['methods'] ?? [])->where('selectable', true)->pluck('key');

        if ($local->isNotEmpty() && ! $local->contains($this->deliveryMethod)) {
            $this->deliveryMethod = $local->first();
        }
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
            $this->loadDeliveryOptions($checkoutApi);
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

            if (! in_array($this->deliveryMethod, ['delivery', 'pickup', 'kasarose_shipping'], true)) {
                $this->deliveryMethod = 'delivery';
            }

            if ($this->deliveryMethod === 'pickup' && ! $this->pickupStationId) {
                $this->error = 'Please choose the pickup station where you will collect your order.';

                return;
            }

            if (! $this->selectedAddressId) {
                $this->error = 'Please select or add a delivery address.';

                return;
            }

            try {
                $orderGroup = $checkoutApi->checkout($this->selectedAddressId, $this->notes, $this->whatsappOptIn, $this->deliveryMethod, $this->deliveryMethod === 'pickup' ? $this->pickupStationId : null);
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
