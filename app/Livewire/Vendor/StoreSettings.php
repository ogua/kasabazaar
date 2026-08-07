<?php

namespace App\Livewire\Vendor;

use App\Services\Kasabazaar\KasabazaarClient;
use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;
use Livewire\WithFileUploads;

class StoreSettings extends Component
{
    use WithFileUploads;

    public string $business_name = '';

    public string $description = '';

    public string $phone = '';

    public string $support_email = '';

    public string $payout_method = '';

    public string $payout_details = '';

    public ?string $logo_url = null;

    public ?string $banner_url = null;

    public $newLogo;

    public $newBanner;

    public function mount(VendorApi $vendorApi): void
    {
        $vendor = $vendorApi->storeSettings();
        $this->business_name = $vendor['business_name'];
        $this->description = $vendor['description'] ?? '';
        $this->phone = $vendor['phone'] ?? '';
        $this->support_email = $vendor['support_email'] ?? '';
        $this->payout_method = $vendor['payout_method'] ?? '';
        $this->logo_url = $vendor['logo_path'] ? asset('storage/'.$vendor['logo_path']) : null;
        $this->banner_url = $vendor['banner_path'] ? asset('storage/'.$vendor['banner_path']) : null;
    }

    public function save(VendorApi $vendorApi): void
    {
        $this->validate([
            'business_name' => 'required|string|max:255',
            'support_email' => 'nullable|email',
        ]);

        $vendorApi->updateStore([
            'business_name' => $this->business_name,
            'description' => $this->description,
            'phone' => $this->phone,
            'support_email' => $this->support_email,
            'payout_method' => $this->payout_method,
            'payout_details' => $this->payout_details,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Store settings updated.');
    }

    public function uploadLogo(KasabazaarClient $client): void
    {
        if (! $this->newLogo) {
            return;
        }

        $client->postMultipart('marketplace/vendor/store/logo', [], [
            ['name' => 'logo', 'contents' => fopen($this->newLogo->getRealPath(), 'r'), 'filename' => $this->newLogo->getClientOriginalName()],
        ]);

        $this->logo_url = $this->newLogo->temporaryUrl();
        $this->newLogo = null;
    }

    public function uploadBanner(KasabazaarClient $client): void
    {
        if (! $this->newBanner) {
            return;
        }

        $client->postMultipart('marketplace/vendor/store/banner', [], [
            ['name' => 'banner', 'contents' => fopen($this->newBanner->getRealPath(), 'r'), 'filename' => $this->newBanner->getClientOriginalName()],
        ]);

        $this->banner_url = $this->newBanner->temporaryUrl();
        $this->newBanner = null;
    }

    public function render()
    {
        return view('livewire.vendor.store-settings')->layout('vendor.layouts.dashboard', ['title' => 'Store Settings']);
    }
}
