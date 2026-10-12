<?php

namespace App\Services;

use App\Enums\AuctionOutcome;
use App\Enums\AuctionStatus;
use App\Enums\EcommerceOrderPaymentStatus;
use App\Exceptions\AuctionException;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\EcommerceOrderGroup;
use App\Models\User;
use App\Notifications\Ecommerce\AuctionOutbid;
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
     * Close every auction whose end time has passed.
     */
    public function closeDue(): int
    {
        $count = 0;

        Auction::dueForClosing()->orderBy('ends_at')->each(function (Auction $auction) use (&$count) {
            $this->close($auction);
            $count++;
        });

        return $count;
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
