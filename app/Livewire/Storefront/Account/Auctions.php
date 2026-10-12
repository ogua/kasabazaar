<?php

namespace App\Livewire\Storefront\Account;

use App\Services\Kasabazaar\AuctionsApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Auctions extends Component
{
    use WithPagination;

    public function render(AuctionsApi $auctionsApi)
    {
        $auctions = [];
        $meta = [];
        $error = null;

        try {
            $response = $auctionsApi->mine($this->getPage());
            $auctions = $response->data;
            $meta = $response->meta ?? [];
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.account.auctions: failed to load auctions', ['message' => $e->getMessage()]);
            $error = 'We\'re having trouble loading your auctions right now. Please try again shortly.';
        }

        return view('livewire.storefront.account.auctions', [
            'auctions' => $auctions,
            'meta' => $meta,
            'error' => $error,
        ])->layout('storefront.layouts.app')->title('My Auctions');
    }
}
