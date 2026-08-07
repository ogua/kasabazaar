<?php

namespace App\Services\Kasabazaar;

class VendorApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function login(string $email, string $password): array
    {
        return $this->client->post('marketplace/vendor/auth/login', ['email' => $email, 'password' => $password])->data;
    }

    public function me(): array
    {
        return $this->client->get('marketplace/vendor/auth/me')->data;
    }

    public function storeSettings(): array
    {
        return $this->client->get('marketplace/vendor/store')->data;
    }

    public function updateStore(array $data): array
    {
        return $this->client->put('marketplace/vendor/store', $data)->data;
    }

    public function products(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/products', $query);
    }

    public function product(string $id): array
    {
        return $this->client->get("marketplace/vendor/products/{$id}")->data;
    }

    public function createProduct(array $data): array
    {
        return $this->client->post('marketplace/vendor/products', $data)->data;
    }

    public function updateProduct(string $id, array $data): array
    {
        return $this->client->put("marketplace/vendor/products/{$id}", $data)->data;
    }

    public function toggleProductActive(string $id): array
    {
        return $this->client->patch("marketplace/vendor/products/{$id}/toggle-active")->data;
    }

    public function categories(): array
    {
        return $this->client->get('marketplace/vendor/categories')->data;
    }

    public function orders(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/orders', $query);
    }

    public function order(string $id): array
    {
        return $this->client->get("marketplace/vendor/orders/{$id}")->data;
    }

    public function processOrder(string $id): void
    {
        $this->client->patch("marketplace/vendor/orders/{$id}/process");
    }

    public function packOrder(string $id): void
    {
        $this->client->patch("marketplace/vendor/orders/{$id}/pack");
    }

    public function dispatchOrder(string $id): void
    {
        $this->client->patch("marketplace/vendor/orders/{$id}/dispatch");
    }

    public function earningsSummary(): array
    {
        return $this->client->get('marketplace/vendor/earnings/summary')->data;
    }

    public function earningsTransactions(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/earnings/transactions', $query);
    }

    public function payouts(): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/payouts');
    }

    public function requestPayout(array $data): array
    {
        return $this->client->post('marketplace/vendor/payouts', $data)->data;
    }

    public function coupons(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/coupons', $query);
    }

    public function createCoupon(array $data): array
    {
        return $this->client->post('marketplace/vendor/coupons', $data)->data;
    }

    public function updateCoupon(string $id, array $data): array
    {
        return $this->client->put("marketplace/vendor/coupons/{$id}", $data)->data;
    }

    public function reviews(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendor/reviews', $query);
    }

    public function replyToReview(string $id, string $reply): void
    {
        $this->client->post("marketplace/vendor/reviews/{$id}/reply", ['reply' => $reply]);
    }
}
