<?php

namespace App\Services\Kasabazaar;

class OrdersApi
{
    public function __construct(protected KasabazaarClient $client) {}

    public function list(array $query = []): KasabazaarResponse
    {
        return $this->client->get('marketplace/orders', $query);
    }

    public function show(string $id): array
    {
        return $this->client->get("marketplace/orders/{$id}")->data ?? [];
    }

    public function tracking(string $id): array
    {
        return $this->client->get("marketplace/orders/{$id}/tracking")->data ?? [];
    }

    public function cancel(string $id, ?string $reason = null): void
    {
        $this->client->delete("marketplace/orders/{$id}", ['reason' => $reason]);
    }

    public function rate(string $id, int $rating, ?string $comment = null): void
    {
        $this->client->post("marketplace/orders/{$id}/rate", ['rating' => $rating, 'comment' => $comment]);
    }
}
