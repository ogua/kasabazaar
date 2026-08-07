<?php

namespace App\Services\Kasabazaar;

class ProductsApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function home(): array
    {
        return $this->client->get('marketplace/home')->data;
    }

    public function categories(): array
    {
        return $this->client->get('marketplace/categories')->data;
    }

    public function categoryProducts(string $categoryId, array $query = []): KasabazaarResponse
    {
        return $this->client->get("marketplace/categories/{$categoryId}/products", $query);
    }

    public function list(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/products', $query);
    }

    public function show(string $productId): array
    {
        return $this->client->get("marketplace/products/{$productId}")->data;
    }

    public function reviews(string $productId, int $page = 1): KasabazaarResponse
    {
        return $this->client->get("marketplace/products/{$productId}/reviews", ['page' => $page]);
    }

    public function submitReview(string $productId, array $data): array
    {
        return $this->client->post("marketplace/products/{$productId}/reviews", $data)->data;
    }

    public function vendors(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/vendors', $query);
    }

    public function vendor(string $slug): array
    {
        return $this->client->get("marketplace/vendors/{$slug}")->data;
    }

    public function vendorProducts(string $slug, array $query = []): KasabazaarResponse
    {
        return $this->client->get("marketplace/vendors/{$slug}/products", $query);
    }
}
