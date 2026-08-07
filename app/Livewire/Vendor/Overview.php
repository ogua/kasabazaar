<?php

namespace App\Livewire\Vendor;

use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;

class Overview extends Component
{
    public array $summary = [];

    public array $recentOrders = [];

    public array $lowStock = [];

    public function mount(VendorApi $vendorApi): void
    {
        $this->summary = $vendorApi->earningsSummary();
        $this->recentOrders = $vendorApi->orders(['per_page' => 5])->data;
    }

    public function render()
    {
        return view('livewire.vendor.overview')->layout('vendor.layouts.dashboard', ['title' => 'Overview']);
    }
}
