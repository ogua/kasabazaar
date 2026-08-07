<?php

namespace App\Services\Kasabazaar;

class WishlistApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function index(): array
    {
        return $this->client->get('marketplace/wishlist')->data;
    }

    public function add(string $productId): void
    {
        $this->client->post("marketplace/wishlist/{$productId}");
    }

    public function remove(string $productId): void
    {
        $this->client->delete("marketplace/wishlist/{$productId}");
    }

    public function toggle(string $productId): void
    {
        $ids = collect($this->index())->pluck('id')->all();

        in_array($productId, $ids, true) ? $this->remove($productId) : $this->add($productId);
    }
}
