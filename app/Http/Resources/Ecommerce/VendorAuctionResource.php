<?php

namespace App\Http\Resources\Ecommerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A vendor's own auction, for their portal. Unlike the public resource it
 * includes the reserve price (it is theirs) and the review status and notes,
 * but never bidder identities.
 *
 * @mixin \App\Models\Auction
 */
class VendorAuctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'condition' => $this->condition,
            'category_id' => $this->category_id,
            'images' => collect($this->image_paths ?? [])->values()->map(fn (string $path, int $index) => [
                'index' => $index,
                'url' => asset('storage/'.$path),
            ])->all(),
            'starting_price_ghs' => (float) $this->starting_price_ghs,
            'reserve_price_ghs' => $this->reserve_price_ghs !== null ? (float) $this->reserve_price_ghs : null,
            'bid_increment_ghs' => (float) $this->bid_increment_ghs,
            'current_price_ghs' => $this->current_price_ghs !== null ? (float) $this->current_price_ghs : null,
            'bids_count' => $this->bids_count,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'extension_minutes' => $this->extension_minutes,
            'status' => $this->status?->value,
            'state' => $this->state(),
            'outcome' => $this->outcome?->value,
            'is_paid' => $this->isPaid(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'is_editable' => in_array($this->status?->value, ['draft', 'pending_review'], true),
        ];
    }
}
