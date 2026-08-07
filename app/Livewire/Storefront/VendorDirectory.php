<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\ProductsApi;
use Livewire\Component;
use Livewire\WithPagination;

class VendorDirectory extends Component
{
    use WithPagination;

    public function render(ProductsApi $productsApi)
    {
        $response = $productsApi->vendors(['page' => $this->getPage(), 'per_page' => 20]);

        return view('livewire.storefront.vendor-directory', [
            'vendors' => $response->data,
            'meta' => $response->meta,
        ])->layout('storefront.layouts.app', ['title' => 'All Vendors']);
    }
}
