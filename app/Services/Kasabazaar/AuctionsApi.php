<?php

namespace App\Services\Kasabazaar;

class AuctionsApi
{
    public function __construct(protected KasabazaarClient $client) {}

    /**
     * @param  array{state?: string, search?: string, category_id?: string, page?: int, per_page?: int}  $query
     */
    public function list(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/auctions', $query);
    }

    public function show(string $slug): array
    {
        return $this->client->get("marketplace/auctions/{$slug}")->data;
    }

    public function bids(string $slug): array
    {
        return $this->client->get("marketplace/auctions/{$slug}/bids")->data;
    }

    public function placeBid(string $slug, float $amountGhs): array
    {
        return $this->client->post("marketplace/auctions/{$slug}/bids", ['amount_ghs' => $amountGhs])->data;
    }

    /**
     * Turn down a second-chance offer; the lot moves on to the next bidder.
     */
    public function decline(string $slug): void
    {
        $this->client->post("marketplace/auctions/{$slug}/decline");
    }

    public function mine(int $page = 1): KasabazaarResponse
    {
        return $this->client->get('marketplace/my-auctions', ['page' => $page]);
    }

    /**
     * Create (or reuse) the winner's order group; its id goes straight to
     * CheckoutApi::initiatePayment().
     */
    public function checkout(string $slug, string $deliveryAddressId, string $notes = ''): array
    {
        return $this->client->post("marketplace/auctions/{$slug}/checkout", [
            'delivery_address_id' => $deliveryAddressId,
            'notes' => $notes,
        ])->data;
    }
}
