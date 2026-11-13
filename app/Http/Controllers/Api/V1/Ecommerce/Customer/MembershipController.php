<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends CustomerBaseController
{
    public function __construct(protected MembershipService $memberships) {}

    /**
     * The membership categories on offer. Public, so shoppers can compare before signing up.
     */
    public function plans(): JsonResponse
    {
        $plans = MembershipPlan::where('is_active', true)->orderBy('sort_order')->get();

        return $this->success($plans->map(fn (MembershipPlan $plan) => $this->planPayload($plan))->values());
    }

    /**
     * The signed-in buyer's membership. The buyers' WhatsApp group link is only
     * included while the membership is running.
     */
    public function show(Request $request): JsonResponse
    {
        $active = $this->memberships->activeFor($request->user());

        return $this->success([
            'active' => $active ? [
                'id' => $active->id,
                'plan' => $this->planPayload($active->plan),
                'status' => $active->status,
                'starts_at' => $active->starts_at,
                'expires_at' => $active->expires_at,
                'days_left' => max(0, (int) now()->diffInDays($active->expires_at, false)),
                'whatsapp_group_url' => config('ecommerce.membership.whatsapp_buyers_group_url') ?: null,
            ] : null,
            'history' => Membership::with('plan')
                ->where('user_id', $request->user()->id)
                ->where('status', '!=', Membership::STATUS_PENDING)
                ->latest('paid_at')
                ->limit(10)
                ->get()
                ->map(fn (Membership $m) => [
                    'plan' => $m->plan->name,
                    'status' => $m->status,
                    'amount_ghs' => (float) $m->amount_ghs,
                    'paid_at' => $m->paid_at,
                    'expires_at' => $m->expires_at,
                ])->values(),
        ]);
    }

    /**
     * Join, renew or switch plan: opens a Paystack checkout for the dues.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|uuid',
            'client' => 'nullable|string|in:web,mobile',
        ]);

        $plan = MembershipPlan::where('is_active', true)->findOrFail($data['plan_id']);

        try {
            $result = $this->memberships->initiate($request->user(), $plan, $data['client'] ?? 'web');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success([
            'gateway' => 'paystack',
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'],
        ], 'Complete the payment to activate your membership.', 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(MembershipPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'tier' => $plan->tier->value,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_ghs' => (float) $plan->price_ghs,
            'duration_months' => $plan->duration_months,
            'product_discount_percent' => (float) $plan->product_discount_percent,
            'exclusive_auctions' => $plan->exclusive_auctions,
            'perks' => $plan->perks ?? [],
        ];
    }
}
