<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A bid as shown in a public bid history: masked bidder, never an ID or
 * contact detail. `is_mine` lets the signed-in shopper spot their own bids.
 *
 * @mixin \App\Models\AuctionBid
 */
class AuctionBidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = auth('sanctum')->user();

        return [
            'id' => $this->id,
            'bidder' => $this->maskedBidderName(),
            'amount_ghs' => (float) $this->amount_ghs,
            'placed_at' => $this->created_at?->toIso8601String(),
            'is_mine' => $viewer !== null && $this->user_id === $viewer->id,
        ];
    }
}
