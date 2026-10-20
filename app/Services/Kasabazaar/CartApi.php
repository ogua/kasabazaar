<?php

namespace App\Services\Kasabazaar;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartApi
{
    public const GUEST_TOKEN_KEY = 'guest_cart_token';

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

    public function removeItem(string $itemId): array
    {
        return $this->client->delete("marketplace/cart/items/{$itemId}", [], $this->guestHeader())->data;
    }

    public function clear(): void
    {
        $this->client->delete('marketplace/cart', [], $this->guestHeader());
    }

    public function applyCoupon(string $code): array
    {
        return $this->client->post('marketplace/cart/coupon', ['code' => $code], $this->guestHeader())->data;
    }

    public function removeCoupon(): array
    {
        return $this->client->delete('marketplace/cart/coupon', [], $this->guestHeader())->data;
    }

    /**
     * The opaque key that identifies an anonymous shopper's cart on the
     * backend. A random token kept in the session, never the session ID
     * itself: the session ID is a credential and must not leave this app.
     * The session survives login (it is regenerated, not cleared), so the
     * same token is handed to the backend to merge the guest cart into the
     * account; logout invalidates the session and starts a fresh cart.
     */
    public static function guestToken(): string
    {
        if (! session()->has(self::GUEST_TOKEN_KEY)) {
            session()->put(self::GUEST_TOKEN_KEY, (string) Str::uuid());
        }

        return session()->get(self::GUEST_TOKEN_KEY);
    }

    private function guestHeader(): array
    {
        if (Auth::check()) {
            return [];
        }

        return ['X-Guest-Session-Id' => self::guestToken()];
    }
}
