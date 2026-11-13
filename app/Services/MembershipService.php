<?php

namespace App\Services;

use App\Enums\MembershipTier;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Buyer memberships: paying dues for a plan, and the benefits an active
 * membership unlocks (product discount, exclusive auctions, the buyers' group).
 * Dues are collected through Paystack in GHS, like marketplace orders.
 */
class MembershipService
{
    public const PAYMENT_TYPE = 'membership';

    /**
     * The member's best running membership, if any.
     */
    public function activeFor(?User $user): ?Membership
    {
        if (! $user) {
            return null;
        }

        return Membership::running()
            ->where('user_id', $user->id)
            ->with('plan')
            ->get()
            ->sortByDesc(fn (Membership $m) => $m->plan->tier->rank())
            ->first();
    }

    public function discountPercentFor(?User $user): float
    {
        return (float) ($this->activeFor($user)?->plan->product_discount_percent ?? 0);
    }

    /**
     * Whether the user holds a running membership of at least the given tier.
     */
    public function meetsTier(?User $user, ?string $requiredTier): bool
    {
        if (! $requiredTier) {
            return true;
        }

        $required = MembershipTier::tryFrom($requiredTier);
        $held = $this->activeFor($user)?->plan->tier;

        return $required === null || ($held !== null && $held->rank() >= $required->rank());
    }

    /**
     * Start (or renew) a membership and open a Paystack checkout for its dues.
     * Renewing the plan already held extends it from its current expiry, so no
     * paid time is lost; the new period only becomes active once payment is verified.
     *
     * @return array{membership: Membership, authorization_url: string, reference: string}
     */
    public function initiate(User $user, MembershipPlan $plan, string $client = 'web'): array
    {
        $membership = Membership::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => Membership::STATUS_PENDING,
            'amount_ghs' => $plan->price_ghs,
        ]);

        $callback = config("ecommerce.checkout_callback_urls.{$client}");

        if (! $callback) {
            throw new \RuntimeException("No checkout callback URL configured for client '{$client}'.");
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $user->email,
                'amount' => (int) round((float) $plan->price_ghs * 100),
                'currency' => 'GHS',
                'callback_url' => $callback,
                'metadata' => [
                    'type' => self::PAYMENT_TYPE,
                    'membership_id' => $membership->id,
                    'user_id' => $user->id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            $membership->delete();

            throw new \RuntimeException($response->json('message') ?? 'Failed to start the membership payment.');
        }

        $membership->update(['payment_gateway' => 'paystack', 'payment_reference' => $response->json('data.reference')]);

        return [
            'membership' => $membership,
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
        ];
    }

    /**
     * Confirm a Paystack payment with Paystack itself and activate the membership.
     * Safe to call twice (the redirect and the webhook both do): the second call is a no-op.
     */
    public function verifyPaystack(string $reference): Membership
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            throw new \RuntimeException('Membership payment verification failed.');
        }

        $membership = Membership::with('plan')->findOrFail($response->json('data.metadata.membership_id'));

        // Never trust the redirect alone: the amount Paystack took must match the dues.
        if ((int) $response->json('data.amount') < (int) round((float) $membership->amount_ghs * 100)) {
            throw new \RuntimeException('The amount paid does not match the membership dues.');
        }

        return $this->activate($membership);
    }

    public function activate(Membership $membership): Membership
    {
        return DB::transaction(function () use ($membership) {
            $membership = Membership::with('plan')->lockForUpdate()->findOrFail($membership->id);

            if ($membership->status === Membership::STATUS_ACTIVE) {
                return $membership;
            }

            // Renewing the same plan stacks onto time already paid for.
            $current = Membership::running()
                ->where('user_id', $membership->user_id)
                ->where('membership_plan_id', $membership->membership_plan_id)
                ->where('id', '!=', $membership->id)
                ->orderByDesc('expires_at')
                ->first();

            $start = $current?->expires_at ?? now();

            $membership->update([
                'status' => Membership::STATUS_ACTIVE,
                'paid_at' => now(),
                'starts_at' => $start,
                'expires_at' => $start->copy()->addMonths($membership->plan->duration_months),
            ]);

            // Any other running membership ends now: a renewal's time was stacked into this
            // period, and a different plan replaces the old one, so benefits never double up.
            Membership::running()
                ->where('user_id', $membership->user_id)
                ->where('id', '!=', $membership->id)
                ->update(['status' => Membership::STATUS_EXPIRED]);

            return $membership->fresh('plan');
        });
    }
}
