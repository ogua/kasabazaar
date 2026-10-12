<?php

namespace App\Livewire\Storefront;

use App\Services\Kasabazaar\AuctionsApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AuctionDetail extends Component
{
    public string $slug;

    public array $auction = [];

    public array $bids = [];

    public bool $notFound = false;

    public ?string $bidAmount = null;

    public ?string $bidError = null;

    public function mount(string $slug, AuctionsApi $auctionsApi): void
    {
        $this->slug = $slug;

        // Guests who click "Sign in to bid" come straight back here afterwards.
        if (! Auth::check()) {
            session()->put('url.intended', route('storefront.auction', $slug));
        }

        try {
            $this->auction = $auctionsApi->show($slug);
            $this->bids = $auctionsApi->bids($slug);
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.auction: failed to load auction', ['auction' => $slug, 'message' => $e->getMessage()]);
            $this->notFound = true;

            return;
        }

        $this->bidAmount = $this->formatAmount($this->auction['next_minimum_bid_ghs'] ?? null);
    }

    /**
     * Polled every few seconds while the auction is live so the price, the
     * bid list and the (possibly extended) end time stay current.
     */
    public function refresh(AuctionsApi $auctionsApi): void
    {
        if ($this->notFound) {
            return;
        }

        try {
            $previousMinimum = $this->auction['next_minimum_bid_ghs'] ?? null;
            $this->auction = $auctionsApi->show($this->slug);
            $this->bids = $auctionsApi->bids($this->slug);

            // Keep the shopper's own figure unless it has fallen below the new minimum.
            $minimum = (float) ($this->auction['next_minimum_bid_ghs'] ?? 0);
            if ($this->bidAmount === null || (float) $this->bidAmount < $minimum || $this->bidAmount === $this->formatAmount($previousMinimum)) {
                $this->bidAmount = $this->formatAmount($minimum);
            }
        } catch (KasabazaarApiException $e) {
            Log::info('storefront.auction: refresh failed', ['auction' => $this->slug, 'message' => $e->getMessage()]);
        }
    }

    public function setQuickBid(float $amount): void
    {
        $this->bidAmount = $this->formatAmount($amount);
        $this->bidError = null;
    }

    public function placeBid(AuctionsApi $auctionsApi): void
    {
        if (! Auth::check()) {
            $this->redirect(route('storefront.login'), navigate: false);

            return;
        }

        $this->bidError = null;
        $minimum = (float) ($this->auction['next_minimum_bid_ghs'] ?? 0);

        $this->validate(
            ['bidAmount' => ['required', 'numeric', 'min:'.$minimum]],
            ['bidAmount.min' => 'Your bid must be at least GHS '.number_format($minimum, 2).'.'],
            ['bidAmount' => 'bid'],
        );

        try {
            $this->auction = $auctionsApi->placeBid($this->slug, (float) $this->bidAmount);
            $this->bids = $auctionsApi->bids($this->slug);
            $this->bidAmount = $this->formatAmount($this->auction['next_minimum_bid_ghs'] ?? null);
            $this->dispatch('toast', type: 'success', message: 'Your bid has been placed. You are the highest bidder.');
        } catch (KasabazaarApiException $e) {
            $this->bidError = $e->getMessage();
            $this->refresh($auctionsApi);
        }
    }

    public function declineOffer(AuctionsApi $auctionsApi): void
    {
        try {
            $auctionsApi->decline($this->slug);
            $this->dispatch('toast', type: 'success', message: 'Offer declined. Thanks for letting us know.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }

        $this->refresh($auctionsApi);
    }

    private function formatAmount(mixed $amount): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 2, '.', '');
    }

    public function render()
    {
        return view('livewire.storefront.auction-detail')
            ->layout('storefront.layouts.app')
            ->title($this->auction['title'] ?? 'Auction');
    }
}
