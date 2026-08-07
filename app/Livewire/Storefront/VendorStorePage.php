<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\ProductsApi;
use Livewire\Component;
use Livewire\WithPagination;

class VendorStorePage extends Component
{
    use HasCartActions, WithPagination;

    public string $slug;

    public array $vendor = [];

    public function mount(string $vendor, ProductsApi $productsApi): void
    {
        $this->slug = $vendor;
        $this->vendor = $productsApi->vendor($vendor);
    }

    public function render(ProductsApi $productsApi)
    {
        $response = $productsApi->vendorProducts($this->slug, ['page' => $this->getPage(), 'per_page' => 20]);

        return view('livewire.storefront.vendor-store-page', [
            'products' => $response->data,
            'meta' => $response->meta,
        ])->layout('storefront.layouts.app', ['title' => $this->vendor['business_name'] ?? 'Vendor Store']);
    }
}
