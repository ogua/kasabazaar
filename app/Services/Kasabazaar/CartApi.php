<?php

namespace App\Services\Kasabazaar;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function show(): array
    {
        return $this->client->get('marketplace/cart', [], $this->guestHeader())->data;
    }

    public function addItem(string $productId, int $quantity = 1): array
    {
        return $this->client->post('marketplace/cart/items', [
            'product_id' => $productId,
            'quantity' => $quantity,
        ], $this->guestHeader())->data;
    }

    public function updateItem(string $itemId, int $quantity): array
    {
        return $this->client->put("marketplace/cart/items/{$itemId}", ['quantity' => $quantity], $this->guestHeader())->data;
    }

    public function removeItem(string $itemId): void
    {
        $this->client->delete("marketplace/cart/items/{$itemId}", [], $this->guestHeader());
    }

    public function clear(): void
    {
        $this->client->delete('marketplace/cart', [], $this->guestHeader());
    }

    public function applyCoupon(string $code): array
    {
        return $this->client->post('marketplace/cart/coupon', ['code' => $code], $this->guestHeader())->data;
    }

    /**
     * kmarket's own Laravel session ID doubles as the guest cart key (gap D.2)
     * so an anonymous shopper's cart survives across requests and merges into
     * their account cart automatically when they log in or register.
     */
    private function guestHeader(): array
    {
        if (Auth::check()) {
            return [];
        }

        return ['X-Guest-Session-Id' => session()->getId() ?: (string) Str::uuid()];
    }
}
