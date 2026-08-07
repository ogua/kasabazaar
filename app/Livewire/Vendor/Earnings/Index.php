<?php

namespace App\Livewire\Vendor\Earnings;

use App\Services\Kasabazaar\KasabazaarApiException;
use App\Services\Kasabazaar\VendorApi;
use Livewire\Component;

class Index extends Component
{
    public array $summary = [];

    public array $transactions = [];

    public bool $showPayoutForm = false;

    public string $amount_ghs = '';

    public string $payout_method = 'momo';

    public string $payout_details = '';

    public string $error = '';

    public function mount(VendorApi $vendorApi): void
    {
        $this->summary = $vendorApi->earningsSummary();
        $this->transactions = $vendorApi->earningsTransactions(['per_page' => 20])->data;
    }

    public function requestPayout(VendorApi $vendorApi): void
    {
        $this->validate([
            'amount_ghs' => 'required|numeric|min:1',
            'payout_method' => 'required|string',
            'payout_details' => 'required|string|max:1000',
        ]);

        try {
            $vendorApi->requestPayout([
                'amount_ghs' => (float) $this->amount_ghs,
                'payout_method' => $this->payout_method,
                'payout_details' => $this->payout_details,
            ]);

            $this->dispatch('toast', type: 'success', message: 'Payout requested.');
            $this->showPayoutForm = false;
            $this->summary = $vendorApi->earningsSummary();
        } catch (KasabazaarApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.vendor.earnings.index')->layout('vendor.layouts.dashboard', ['title' => 'Earnings']);
    }
}
