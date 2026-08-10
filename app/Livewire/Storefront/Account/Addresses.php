<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\CheckoutApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Addresses extends Component
{
    public array $addresses = [];

    public bool $showForm = false;

    public string $full_name = '';

    public string $phone = '';

    public string $region = '';

    public string $city = '';

    public string $street = '';

    public ?string $error = null;

    public function mount(CheckoutApi $checkoutApi): void
    {
        try {
            $this->addresses = $checkoutApi->addresses();
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.addresses: failed to load addresses', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your addresses right now. Please refresh or try again shortly.';
        }
    }

    public function save(CheckoutApi $checkoutApi): void
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'region' => 'required|string|max:100',
            'city' => 'required|string|max:100',
        ]);

        try {
            $checkoutApi->createAddress([
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'country' => 'Ghana',
                'region' => $this->region,
                'city' => $this->city,
                'street' => $this->street,
            ]);

            $this->addresses = $checkoutApi->addresses();
            $this->reset(['showForm', 'full_name', 'phone', 'region', 'city', 'street']);
            $this->dispatch('toast', type: 'success', message: 'Address saved.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function delete(CheckoutApi $checkoutApi, string $id): void
    {
        try {
            $checkoutApi->deleteAddress($id);
            $this->addresses = $checkoutApi->addresses();
            $this->dispatch('toast', type: 'success', message: 'Address deleted.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function setDefault(CheckoutApi $checkoutApi, string $id): void
    {
        try {
            $checkoutApi->setDefaultAddress($id);
            $this->addresses = $checkoutApi->addresses();
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.storefront.account.addresses')->layout('storefront.layouts.app', ['title' => 'My Addresses']);
    }
}
