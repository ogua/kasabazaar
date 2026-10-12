<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\AuctionsApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AuctionListing extends Component
{
    use WithPagination;

    /** @var array<string, string> */
    public const STATES = [
        'current' => 'Live & upcoming',
        'live' => 'Live now',
        'upcoming' => 'Opening soon',
        'ended' => 'Recently ended',
    ];

    #[Url]
    public string $state = 'current';

    #[Url]
    public string $search = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function setState(string $state): void
    {
        $this->state = array_key_exists($state, self::STATES) ? $state : 'current';
        $this->resetPage();
    }

    public function render(AuctionsApi $auctionsApi)
    {
        $auctions = [];
        $meta = [];
        $error = null;

        try {
            $response = $auctionsApi->list(array_filter([
                'state' => array_key_exists($this->state, self::STATES) ? $this->state : 'current',
                'search' => $this->search,
                'page' => $this->getPage(),
                'per_page' => 20,
            ]));

            $auctions = $response->data;
            $meta = $response->meta ?? [];
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.auctions: failed to load auctions', ['message' => $e->getMessage()]);
            $error = 'We\'re having trouble loading auctions right now. Please try again shortly.';
        }

        return view('livewire.storefront.auction-listing', [
            'auctions' => $auctions,
            'meta' => $meta,
            'error' => $error,
            'states' => self::STATES,
        ])->layout('storefront.layouts.app')->title('Auctions');
    }
}
