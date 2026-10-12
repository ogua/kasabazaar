<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A buyer who was offered an auction lot and did not pay in time.
 */
class AuctionPaymentDefault extends Model
{
    use HasUuids;

    public const KIND_WINNER = 'winner';

    public const KIND_SECOND_CHANCE = 'second_chance';

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
}
