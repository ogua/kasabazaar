<?php

namespace App\Livewire\Vendor\Products;

use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function toggleActive(VendorApi $vendorApi, string $id): void
    {
        $vendorApi->toggleProductActive($id);
    }

    public function render(VendorApi $vendorApi)
    {
        $response = $vendorApi->products(['page' => $this->getPage(), 'per_page' => 20]);

        return view('livewire.vendor.products.index', [
            'products' => $response->data,
            'meta' => $response->meta,
        ])->layout('vendor.layouts.dashboard', ['title' => 'Products']);
    }
}
