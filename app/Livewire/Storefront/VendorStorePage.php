<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\HasCartActions;
use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class VendorStorePage extends Component
{
    use HasCartActions, WithPagination;

    public string $slug;

    public array $vendorProfile = [];

    public bool $notFound = false;

    public function mount(string $vendor, ProductsApi $productsApi): void
    {
        $this->slug = $vendor;

        try {
            $this->vendorProfile = $productsApi->vendor($vendor);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.vendor: failed to load vendor', ['vendor' => $vendor, 'message' => $e->getMessage()]);
            $this->notFound = true;
        }
    }

    public function render(ProductsApi $productsApi)
    {
        $products = [];
        $meta = [];
        $error = null;

        if (! $this->notFound) {
            try {
                $response = $productsApi->vendorProducts($this->slug, ['page' => $this->getPage(), 'per_page' => 20]);
                $products = $response->data;
                $meta = $response->meta;
            } catch (KasabazaarApiException $e) {
                Log::warning('storefront.vendor: failed to load vendor products', ['vendor' => $this->slug, 'message' => $e->getMessage()]);
                $error = 'We\'re having trouble loading this vendor\'s products right now. Please try again shortly.';
            }
        }

        return view('livewire.storefront.vendor-store-page', [
            'vendor' => $this->vendorProfile,
            'products' => $products,
            'meta' => $meta,
            'error' => $error,
        ])->layout('storefront.layouts.app', [
            'title' => $this->vendorProfile['business_name'] ?? ($this->notFound ? 'Vendor Not Found' : 'Vendor Store'),
        ]);
    }
}
