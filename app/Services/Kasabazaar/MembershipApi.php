<?php

namespace App\Services\Kasabazaar;

class MembershipApi
{
    public function __construct(protected KasabazaarClient $client) {}

    /**
     * The membership categories, lowest tier first.
     *
     * @return array<int, array{id: string, tier: string, name: string, description: ?string, price_ghs: float, duration_months: int, product_discount_percent: float, exclusive_auctions: bool, perks: array<int, string>}>
     */
    public function plans(): array
    {
        return $this->client->get('marketplace/membership/plans')->data;
    }

    /**
     * The signed-in buyer's membership: `active` (or null) and recent `history`.
     *
     * @return array{active: ?array<string, mixed>, history: array<int, array<string, mixed>>}
     */
    public function mine(): array
    {
        return $this->client->get('marketplace/membership')->data;
    }

    /**
     * Join, renew or switch plan. Returns the Paystack checkout to send the buyer to.
     *
     * @return array{gateway: string, authorization_url: string, reference: string}
     */
    public function subscribe(string $planId): array
    {
        return $this->client->post('marketplace/membership/subscribe', ['plan_id' => $planId, 'client' => 'web'])->data;
    }
}
