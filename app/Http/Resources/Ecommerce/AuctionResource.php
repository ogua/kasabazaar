<?php

namespace App\Http\Resources\Ecommerce;

use App\Enums\AuctionOutcome;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public view of an auction. The reserve price is never included — only
 * whether one exists and whether it has been met. The `viewer` block is
 * present only for an authenticated shopper and only ever describes them.
 *
 * @mixin \App\Models\Auction
 */
class AuctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = auth('sanctum')->user();
        $isWinner = $viewer && $this->outcome === AuctionOutcome::Sold && $this->winner_user_id === $viewer->id;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'condition' => $this->condition,
            'images' => $this->imageUrls(),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor ? [
                'business_name' => $this->vendor->business_name,
                'slug' => $this->vendor->slug,
            ] : null),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'starting_price_ghs' => (float) $this->starting_price_ghs,
            'current_price_ghs' => $this->current_price_ghs !== null ? (float) $this->current_price_ghs : null,
            'bid_increment_ghs' => (float) $this->bid_increment_ghs,
            'next_minimum_bid_ghs' => $this->nextMinimumBid(),
            'bids_count' => $this->bids_count,
            'has_reserve' => $this->hasReserve(),
            'reserve_met' => $this->reserveMet(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'extension_minutes' => $this->extension_minutes,
            'state' => $this->state(),
            'outcome' => $this->outcome?->value,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'viewer' => $this->when($viewer !== null, fn () => [
                // Lists preload the viewer's bid auction IDs to avoid a query per row.
                'has_bid' => $request->attributes->has('auction_ids_bid_on')
                    ? in_array($this->id, (array) $request->attributes->get('auction_ids_bid_on'), true)
                    : $this->bids()->where('user_id', $viewer->id)->exists(),
                'is_leading' => $this->winning_bid_id !== null && $this->winningBid?->user_id === $viewer->id,
                'is_winner' => $isWinner,
                'payment_due_at' => $isWinner ? $this->payment_due_at?->toIso8601String() : null,
                'is_paid' => $isWinner && $this->isPaid(),
                'order_group_id' => $isWinner ? $this->order_group_id : null,
            ]),
        ];
    }
}
