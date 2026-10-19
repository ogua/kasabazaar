<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\StartsPayment;
use App\Services\Kasabazaar\AuctionsApi;
use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * The winning bidder's checkout. The backend turns the win into a one-item
 * order at the winning price; payment then runs through exactly the same
 * Paystack/Stripe hand-off and callback page as a cart checkout.
 */
class AuctionPayment extends Component
{
    use StartsPayment;

    public string $slug;

    public array $auction = [];

    public array $addresses = [];

    public string $selectedAddressId = '';

    public string $notes = '';

    public string $error = '';

    public bool $notWinner = false;

    public bool $showNewAddressForm = false;

    public string $full_name = '';

    public string $phone = '';

    public string $region = '';

    public string $city = '';

    public string $street = '';

    public function mount(string $slug, AuctionsApi $auctionsApi, CheckoutApi $checkoutApi): void
    {
        $this->slug = $slug;

        try {
            $this->auction = $auctionsApi->show($slug);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.auction.pay: failed to load auction', ['auction' => $slug, 'message' => $e->getMessage()]);
            $this->notWinner = true;

            return;
        }

        $viewer = $this->auction['viewer'] ?? [];

        if (! ($viewer['is_winner'] ?? false)) {
            $this->notWinner = true;

            return;
        }

        if ($viewer['is_paid'] ?? false) {
            $this->redirect(route('storefront.auction', $slug), navigate: false);

            return;
        }

        try {
            $this->addresses = $checkoutApi->addresses();
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.auction.pay: failed to load addresses', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your addresses right now. Please refresh or try again shortly.';
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

    public function pay(AuctionsApi $auctionsApi, CheckoutApi $checkoutApi): void
    {
        if (! $this->selectedAddressId) {
            $this->error = 'Please select or add a delivery address.';

            return;
        }

        try {
            $orderGroup = $auctionsApi->checkout($this->slug, $this->selectedAddressId, $this->notes);
            $this->error = $this->startPayment($checkoutApi, $orderGroup['id']) ?? '';
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.storefront.auction-payment')
            ->layout('storefront.layouts.app')
            ->title('Pay for your auction win');
    }
}
