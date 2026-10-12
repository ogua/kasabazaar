<?php

namespace App\Services;

use App\Enums\AuctionOutcome;
use App\Enums\AuctionStatus;
use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Exceptions\AuctionException;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionPaymentDefault;
use App\Models\EcommerceOrderGroup;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\User;
use App\Notifications\Ecommerce\AuctionOutbid;
use App\Notifications\Ecommerce\AuctionSecondChance;
use App\Notifications\Ecommerce\AuctionWon;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public function __construct(protected EcommerceOrderService $orderService) {}

    /**
     * Place a bid. The auction row is locked for the duration so two bids
     * racing for the same price cannot both be accepted; every rule is
     * re-checked against the locked row, not the copy the caller loaded.
     */
    public function placeBid(Auction $auction, User $user, float $amount, ?string $ipAddress = null): AuctionBid
    {
        [$bid, $outbidUser] = DB::transaction(function () use ($auction, $user, $amount, $ipAddress) {
            /** @var Auction $locked */
            $locked = Auction::lockForUpdate()->findOrFail($auction->id);

            if (! $locked->isLive()) {
                throw new AuctionException(match ($locked->state()) {
                    Auction::STATE_UPCOMING => 'Bidding has not opened yet for this auction.',
                    default => 'This auction has ended and is no longer accepting bids.',
                });
            }

            if ($locked->vendor?->user_id === $user->id) {
                throw new AuctionException('You cannot bid on your own auction.');
            }

            if ($this->isBarredFromBidding($user)) {
                throw new AuctionException('Your account cannot bid right now because of unpaid auction wins. Please contact us to restore bidding.');
            }

            $leadingBid = $locked->winning_bid_id ? AuctionBid::find($locked->winning_bid_id) : null;

            if ($leadingBid?->user_id === $user->id) {
                throw new AuctionException('You are already the highest bidder.');
            }

            $amount = round($amount, 2);
            $minimum = $locked->nextMinimumBid();

            if ($amount < $minimum) {
                throw new AuctionException('Your bid must be at least GHS '.number_format($minimum, 2).'.');
            }

            if ($amount > (float) config('ecommerce.auction_max_bid_ghs')) {
                throw new AuctionException('That bid is higher than we can accept online. Please contact us to bid this amount.');
            }

            $bid = AuctionBid::create([
                'auction_id' => $locked->id,
                'user_id' => $user->id,
                'amount_ghs' => $amount,
                'ip_address' => $ipAddress,
            ]);

            $updates = [
                'current_price_ghs' => $amount,
                'bids_count' => $locked->bids_count + 1,
                'winning_bid_id' => $bid->id,
            ];

            // Anti-sniping: a bid in the closing window extends the auction so
            // other bidders get a fair chance to respond.
            $window = max(0, (int) $locked->extension_minutes);
            if ($window > 0 && $locked->ends_at->diffInSeconds(now(), true) < $window * 60) {
                $updates['ends_at'] = now()->addMinutes($window);
            }

            $locked->update($updates);

            return [$bid, $leadingBid?->user];
        });

        $outbidUser?->notify(new AuctionOutbid($auction->fresh()));

        return $bid;
    }

    /**
     * Settle an auction whose clock has run out: pick the outcome, record the
     * winner and open the payment window. Safe to call repeatedly — an
     * auction that is already settled is left untouched.
     */
    public function close(Auction $auction): Auction
    {
        $closed = DB::transaction(function () use ($auction) {
            /** @var Auction $locked */
            $locked = Auction::lockForUpdate()->findOrFail($auction->id);

            if ($locked->status !== AuctionStatus::Published || $locked->ends_at->isFuture()) {
                return null;
            }

            $winningBid = $locked->winning_bid_id ? AuctionBid::find($locked->winning_bid_id) : null;

            $outcome = match (true) {
                $winningBid === null => AuctionOutcome::NoBids,
                ! $locked->reserveMet() => AuctionOutcome::ReserveNotMet,
                default => AuctionOutcome::Sold,
            };

            $locked->update([
                'status' => AuctionStatus::Ended,
                'outcome' => $outcome,
                'closed_at' => now(),
                'winner_user_id' => $outcome === AuctionOutcome::Sold ? $winningBid->user_id : null,
                'payment_due_at' => $outcome === AuctionOutcome::Sold
                    ? now()->addHours((int) config('ecommerce.auction_payment_window_hours'))
                    : null,
            ]);

            return $locked;
        });

        if ($closed?->outcome === AuctionOutcome::Sold) {
            $closed->winner?->notify(new AuctionWon($closed));
        }

        return $closed ?? $auction->fresh();
    }

    /**
     * Close every auction whose end time has passed, then move on any sold lot
     * whose buyer let the payment window lapse.
     */
    public function closeDue(): int
    {
        $count = 0;

        Auction::dueForClosing()->orderBy('ends_at')->each(function (Auction $auction) use (&$count) {
            $this->close($auction);
            $count++;
        });

        Auction::overdueForPayment()->orderBy('payment_due_at')->each(function (Auction $auction) use (&$count) {
            $this->handleLapsedPayment($auction);
            $count++;
        });

        return $count;
    }

    /**
     * The current buyer did not pay in time (or declined a second-chance
     * offer): record it, cancel their unpaid order, and offer the lot to the
     * next-highest bidder who has not already had it — at that bidder's own
     * highest bid, and only if it meets the reserve. With nobody left, or
     * after the configured number of second chances, the lot ends as Unpaid
     * for staff to relist.
     */
    public function handleLapsedPayment(Auction $auction, bool $declined = false): Auction
    {
        $offered = DB::transaction(function () use ($auction, $declined) {
            /** @var Auction $locked */
            $locked = Auction::lockForUpdate()->with('orderGroup.orders')->findOrFail($auction->id);

            $stillOwed = $locked->outcome === AuctionOutcome::Sold
                && $locked->winner_user_id !== null
                && ! $locked->isPaid()
                && ($declined || $locked->payment_due_at?->isPast());

            if (! $stillOwed) {
                return null;
            }

            AuctionPaymentDefault::create([
                'auction_id' => $locked->id,
                'user_id' => $locked->winner_user_id,
                'amount_ghs' => $locked->current_price_ghs,
                'kind' => $locked->offer_round === 1 ? AuctionPaymentDefault::KIND_WINNER : AuctionPaymentDefault::KIND_SECOND_CHANCE,
            ]);

            $this->cancelUnpaidOrder($locked, $declined ? 'Second-chance offer declined.' : 'Auction payment window expired.');

            $alreadyOffered = $locked->paymentDefaults()->pluck('user_id')->push($locked->winner_user_id)->unique();
            $maxRounds = 1 + (int) config('ecommerce.auction_max_second_chances');

            $nextBid = $locked->offer_round < $maxRounds
                ? $locked->bids()
                    ->whereNotIn('user_id', $alreadyOffered)
                    ->when($locked->hasReserve(), fn ($q) => $q->where('amount_ghs', '>=', $locked->reserve_price_ghs))
                    ->orderByDesc('amount_ghs')
                    ->orderBy('created_at')
                    ->first()
                : null;

            if (! $nextBid) {
                $locked->update([
                    'outcome' => AuctionOutcome::Unpaid,
                    'winner_user_id' => null,
                    'payment_due_at' => null,
                    'order_group_id' => null,
                ]);

                return null;
            }

            $locked->update([
                'winner_user_id' => $nextBid->user_id,
                'winning_bid_id' => $nextBid->id,
                'current_price_ghs' => $nextBid->amount_ghs,
                'payment_due_at' => now()->addHours((int) config('ecommerce.auction_payment_window_hours')),
                'order_group_id' => null,
                'offer_round' => $locked->offer_round + 1,
            ]);

            return $locked;
        });

        $offered?->winner?->notify(new AuctionSecondChance($offered));

        return $auction->fresh();
    }

    /**
     * A second-chance buyer may turn the offer down; it then moves on at once.
     * The original winner cannot — their winning bid was binding.
     */
    public function declineOffer(Auction $auction, User $user): Auction
    {
        if ($auction->outcome !== AuctionOutcome::Sold || $auction->winner_user_id !== $user->id) {
            throw new AuctionException('There is no offer on this auction for you to decline.');
        }

        if ($auction->offer_round <= 1) {
            throw new AuctionException('A winning bid is binding and cannot be declined. Please contact us if there is a problem.');
        }

        if ($auction->isPaid()) {
            throw new AuctionException('This auction has already been paid for.');
        }

        return $this->handleLapsedPayment($auction, declined: true);
    }

    /**
     * Repeat defaulters lose bidding rights. Lapsed second-chance offers are
     * not held against anyone — accepting one is optional.
     */
    public function isBarredFromBidding(User $user): bool
    {
        $limit = (int) config('ecommerce.auction_max_payment_defaults');

        return $limit > 0 && AuctionPaymentDefault::where('user_id', $user->id)
            ->where('kind', AuctionPaymentDefault::KIND_WINNER)
            ->count() >= $limit;
    }

    /**
     * Staff action: copy a finished or cancelled lot into a fresh draft with
     * a new schedule, keeping its photos, prices and seller.
     */
    public function relist(Auction $auction, ?User $by = null): Auction
    {
        return Auction::create([
            'vendor_id' => $auction->vendor_id,
            'category_id' => $auction->category_id,
            'title' => $auction->title,
            'description' => $auction->description,
            'condition' => $auction->condition,
            'image_paths' => $auction->image_paths,
            'starting_price_ghs' => $auction->starting_price_ghs,
            'reserve_price_ghs' => $auction->reserve_price_ghs,
            'bid_increment_ghs' => $auction->bid_increment_ghs,
            'extension_minutes' => $auction->extension_minutes,
            'starts_at' => now()->addHour()->startOfHour(),
            'ends_at' => now()->addDays(7)->startOfHour(),
            'status' => AuctionStatus::Draft,
            'created_by' => $by?->id,
        ])->fresh();
    }

    /**
     * Vendor action: hand a draft lot to staff for review. Vendors can never
     * publish a lot themselves.
     */
    public function submitForReview(Auction $auction): Auction
    {
        if ($auction->status !== AuctionStatus::Draft) {
            throw new AuctionException('Only a draft auction can be submitted for review.');
        }

        if (empty($auction->image_paths)) {
            throw new AuctionException('Add at least one photo before submitting the auction for review.');
        }

        if ($auction->ends_at->isPast() || $auction->ends_at->lessThanOrEqualTo($auction->starts_at)) {
            throw new AuctionException('Set a closing time in the future, after bidding opens.');
        }

        $auction->update([
            'status' => AuctionStatus::PendingReview,
            'submitted_at' => now(),
            'review_notes' => null,
        ]);

        return $auction;
    }

    public function approve(Auction $auction): Auction
    {
        if ($auction->status !== AuctionStatus::PendingReview) {
            throw new AuctionException('Only an auction awaiting review can be approved.');
        }

        if ($auction->ends_at->isPast()) {
            throw new AuctionException('This auction\'s closing time has already passed. Ask the vendor to reschedule it.');
        }

        $auction->update(['status' => AuctionStatus::Published, 'review_notes' => null]);

        return $auction;
    }

    public function reject(Auction $auction, string $reason): Auction
    {
        if ($auction->status !== AuctionStatus::PendingReview) {
            throw new AuctionException('Only an auction awaiting review can be sent back.');
        }

        $auction->update(['status' => AuctionStatus::Draft, 'review_notes' => $reason]);

        return $auction;
    }

    private function cancelUnpaidOrder(Auction $auction, string $reason): void
    {
        $group = $auction->orderGroup;

        if (! $group || $group->payment_status === EcommerceOrderPaymentStatus::Paid) {
            return;
        }

        $group->update(['payment_status' => EcommerceOrderPaymentStatus::Failed->value]);

        foreach ($group->orders as $order) {
            $order->update([
                'status' => EcommerceOrderStatus::Cancelled->value,
                'payment_status' => EcommerceOrderPaymentStatus::Failed->value,
                'cancelled_reason' => $reason,
            ]);

            EcommerceOrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => EcommerceOrderStatus::Cancelled->value,
                'notes' => $reason,
            ]);
        }
    }

    /**
     * Create (or reuse) the order group the winner pays through. Reusing a
     * still-pending group means a dropped payment session can be retried
     * without creating a second order.
     */
    public function checkout(Auction $auction, User $user, string $deliveryAddressId, string $notes = ''): EcommerceOrderGroup
    {
        return DB::transaction(function () use ($auction, $user, $deliveryAddressId, $notes) {
            /** @var Auction $locked */
            $locked = Auction::lockForUpdate()->findOrFail($auction->id);

            if ($locked->outcome !== AuctionOutcome::Sold || $locked->winner_user_id !== $user->id) {
                throw new AuctionException('Only the winning bidder can pay for this auction.');
            }

            $existing = $locked->orderGroup;

            if ($existing?->payment_status === EcommerceOrderPaymentStatus::Paid) {
                throw new AuctionException('This auction has already been paid for.');
            }

            if ($existing?->payment_status === EcommerceOrderPaymentStatus::Pending) {
                return $existing->load(['orders.items', 'orders.deliveryDetail', 'orders.statusHistory', 'orders.vendor']);
            }

            if ($locked->payment_due_at?->isPast()) {
                throw new AuctionException('The payment window for this auction has closed. Please contact us.');
            }

            $group = $this->orderService->createFromAuction($locked, $user, $deliveryAddressId, $notes);

            $locked->update(['order_group_id' => $group->id]);

            return $group;
        });
    }

    /**
     * Staff action: withdraw an auction. Ended auctions cannot be cancelled —
     * they have a recorded result.
     */
    public function cancel(Auction $auction): void
    {
        if ($auction->status === AuctionStatus::Ended) {
            throw new AuctionException('An auction that has already ended cannot be cancelled.');
        }

        $auction->update(['status' => AuctionStatus::Cancelled]);
    }
}
