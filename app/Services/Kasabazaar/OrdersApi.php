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

    /**
     * Opens the Paystack checkout for the delivery cost the shipping team entered.
     *
     * @return array{gateway: string, authorization_url: string, reference: string}
     */
    public function payDeliveryFee(string $id): array
    {
        return $this->client->post("marketplace/orders/{$id}/delivery-fee/pay", ['client' => 'web'])->data;
    }

    public function requestReturn(string $id, string $reason, ?string $details = null): array
    {
        return $this->client->post("marketplace/orders/{$id}/returns", ['reason' => $reason, 'details' => $details])->data;
    }

    public function trackPublic(string $orderNumber, string $contact): array
    {
        return $this->client->post('marketplace/track-order', [
            'order_number' => $orderNumber,
            'contact' => $contact,
        ])->data;
    }
}
