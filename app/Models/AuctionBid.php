<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuctionBid extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount_ghs' => 'decimal:2',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public bid histories never show who is bidding: "Kwame Mensah" is
     * shown as "K***e", enough for a bidder to recognise their own bids.
     */
    public function maskedBidderName(): string
    {
        $name = trim((string) $this->user?->name);

        if (Str::length($name) < 2) {
            return 'Bidder';
        }

        $first = Str::of($name)->before(' ');

        return Str::upper(Str::substr($first, 0, 1)).'***'.Str::lower(Str::substr($first, -1));
    }
}
