<?php

namespace App\Models;

use App\Enums\AuctionOutcome;
use App\Enums\AuctionStatus;
use App\Enums\EcommerceOrderPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A single marketplace auction lot. Lots are sold once, to the highest bidder,
 * and are intentionally separate from ecommerce_products so they can never be
 * bought from the catalogue while an auction is running.
 */
class Auction extends Model
{
    use HasUuids, SoftDeletes;

    public const STATE_UPCOMING = 'upcoming';

    public const STATE_LIVE = 'live';

    public const STATE_ENDED = 'ended';

    public const STATE_CANCELLED = 'cancelled';

    public const STATE_DRAFT = 'draft';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'image_paths' => 'array',
            'starting_price_ghs' => 'decimal:2',
            'reserve_price_ghs' => 'decimal:2',
            'bid_increment_ghs' => 'decimal:2',
            'current_price_ghs' => 'decimal:2',
            'bids_count' => 'integer',
            'extension_minutes' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'original_ends_at' => 'datetime',
            'closed_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'status' => AuctionStatus::class,
            'outcome' => AuctionOutcome::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $auction) {
            $auction->vendor_id ??= Vendor::platform()->id;
            $auction->slug ??= Str::slug($auction->title).'-'.Str::lower(Str::random(6));
            $auction->original_ends_at ??= $auction->ends_at;
            $auction->status ??= AuctionStatus::Draft;
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EcommerceCategory::class, 'category_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function winningBid(): BelongsTo
    {
        return $this->belongsTo(AuctionBid::class, 'winning_bid_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function orderGroup(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrderGroup::class, 'order_group_id');
    }

    /**
     * Published and not cancelled — the auctions shoppers may see.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [AuctionStatus::Published, AuctionStatus::Ended]);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', AuctionStatus::Published)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', AuctionStatus::Published)->where('starts_at', '>', now());
    }

    /**
     * Published auctions whose clock has run out but which the close job has
     * not settled yet.
     */
    public function scopeDueForClosing(Builder $query): Builder
    {
        return $query->where('status', AuctionStatus::Published)->where('ends_at', '<=', now());
    }

    public function state(): string
    {
        return match (true) {
            $this->status === AuctionStatus::Draft => self::STATE_DRAFT,
            $this->status === AuctionStatus::Cancelled => self::STATE_CANCELLED,
            $this->status === AuctionStatus::Ended, $this->ends_at->isPast() => self::STATE_ENDED,
            $this->starts_at->isFuture() => self::STATE_UPCOMING,
            default => self::STATE_LIVE,
        };
    }

    public function isLive(): bool
    {
        return $this->state() === self::STATE_LIVE;
    }

    /**
     * The lowest amount the next bid may be: the starting price for the first
     * bid, otherwise the current price plus the increment.
     */
    public function nextMinimumBid(): float
    {
        if ($this->bids_count === 0 || $this->current_price_ghs === null) {
            return (float) $this->starting_price_ghs;
        }

        return round((float) $this->current_price_ghs + (float) $this->bid_increment_ghs, 2);
    }

    public function hasReserve(): bool
    {
        return $this->reserve_price_ghs !== null && (float) $this->reserve_price_ghs > 0;
    }

    public function reserveMet(): bool
    {
        return ! $this->hasReserve()
            || ($this->current_price_ghs !== null && (float) $this->current_price_ghs >= (float) $this->reserve_price_ghs);
    }

    public function isPaid(): bool
    {
        return $this->orderGroup?->payment_status === EcommerceOrderPaymentStatus::Paid;
    }

    /**
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return collect($this->image_paths ?? [])
            ->filter()
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }
}
