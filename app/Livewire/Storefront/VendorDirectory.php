<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\ProductsApi;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class VendorDirectory extends Component
{
    use WithPagination;

    public function render(ProductsApi $productsApi)
    {
        $vendors = [];
        $meta = [];
        $error = null;

        try {
            $response = $productsApi->vendors(['page' => $this->getPage(), 'per_page' => 24]);
            $vendors = $response->data;
            $meta = $response->meta;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.vendors: failed to load vendors', ['message' => $e->getMessage()]);
            $error = 'We\'re having trouble loading vendors right now. Please try again shortly.';
        }

        return view('livewire.storefront.vendor-directory', [
            'vendors' => $vendors,
            'meta' => $meta,
            'error' => $error,
        ])->layout('storefront.layouts.app', ['title' => 'All Vendors']);
    }
}
