<?php

namespace App\Livewire\Vendor\Coupons;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;

class Index extends Component
{
    public array $coupons = [];

    public bool $showForm = false;

    public string $code = '';

    public string $type = 'percentage';

    public string $value = '';

    public string $min_order_amount_ghs = '';

    public string $expires_at = '';

    public string $error = '';

    public function mount(VendorApi $vendorApi): void
    {
        $this->coupons = $vendorApi->coupons(['per_page' => 50])->data;
    }

    public function create(VendorApi $vendorApi): void
    {
        $this->validate([
            'code' => 'required|string|max:50',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
        ]);

        try {
            $vendorApi->createCoupon(array_filter([
                'code' => strtoupper($this->code),
                'type' => $this->type,
                'value' => (float) $this->value,
                'min_order_amount_ghs' => $this->min_order_amount_ghs !== '' ? (float) $this->min_order_amount_ghs : null,
                'expires_at' => $this->expires_at ?: null,
            ]));

            $this->coupons = $vendorApi->coupons(['per_page' => 50])->data;
            $this->reset(['showForm', 'code', 'value', 'min_order_amount_ghs', 'expires_at']);
            $this->dispatch('toast', type: 'success', message: 'Coupon created.');
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function toggleActive(VendorApi $vendorApi, string $id, bool $current): void
    {
        $vendorApi->updateCoupon($id, ['is_active' => ! $current]);
        $this->coupons = $vendorApi->coupons(['per_page' => 50])->data;
    }

    public function render()
    {
        return view('livewire.vendor.coupons.index')->layout('vendor.layouts.dashboard', ['title' => 'Coupons']);
    }
}
