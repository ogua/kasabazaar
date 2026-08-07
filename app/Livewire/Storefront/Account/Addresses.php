<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\CheckoutApi;
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

    public function mount(CheckoutApi $checkoutApi): void
    {
        $this->addresses = $checkoutApi->addresses();
    }

    public function save(CheckoutApi $checkoutApi): void
    {
        $this->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'region' => 'required|string|max:100',
            'city' => 'required|string|max:100',
        ]);

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
    }

    public function delete(CheckoutApi $checkoutApi, string $id): void
    {
        $checkoutApi->deleteAddress($id);
        $this->addresses = $checkoutApi->addresses();
    }

    public function setDefault(CheckoutApi $checkoutApi, string $id): void
    {
        $checkoutApi->setDefaultAddress($id);
        $this->addresses = $checkoutApi->addresses();
    }

    public function render()
    {
        return view('livewire.storefront.account.addresses')->layout('storefront.layouts.app', ['title' => 'My Addresses']);
    }
}
