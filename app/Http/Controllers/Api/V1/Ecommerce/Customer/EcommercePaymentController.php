<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderGroup;
use App\Models\Membership;
use App\Services\DeliveryFeeService;
use App\Services\EcommercePaymentService;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommercePaymentController extends CustomerBaseController
{
    public function __construct(protected EcommercePaymentService $paymentService) {}

    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_group_id' => 'required|uuid',
            'client' => 'nullable|string|in:web,mobile',
        ]);

        $group = EcommerceOrderGroup::where('user_id', auth()->id())
            ->with('orders.deliveryDetail')
            ->findOrFail($data['order_group_id']);

        abort_unless($group->payment_status === EcommerceOrderPaymentStatus::Pending, 422, 'This order has already been paid or is not ready for payment.');

        $country = $group->orders->first()?->deliveryDetail?->country ?? 'Ghana';
        $gateway = $this->paymentService->getGateway($country);

        $result = $gateway === 'paystack'
            ? $this->paymentService->initiatePaystack($group, auth()->user(), $data['client'] ?? 'web')
            : $this->paymentService->initiateStripe($group, auth()->user());

        return $this->success(array_merge(['gateway' => $gateway], $result));
    }

    public function verifyPaystack(Request $request): JsonResponse
    {
        $data = $request->validate(['reference' => 'required|string']);

        // Membership dues share this callback with marketplace orders.
        $feeOrder = EcommerceOrder::where('user_id', auth()->id())->where('delivery_fee_payment_reference', $data['reference'])->first();

        if ($feeOrder) {
            try {
                $feeOrder = app(DeliveryFeeService::class)->verifyPaystack($data['reference']);
            } catch (\RuntimeException $e) {
                return $this->error($e->getMessage(), 422);
            }

            return $this->success([
                'type' => 'delivery_fee',
                'order_id' => $feeOrder->id,
                'order_number' => $feeOrder->order_number,
            ], 'Delivery payment confirmed.');
        }

        $membership = Membership::where('user_id', auth()->id())->where('payment_reference', $data['reference'])->first();

        if ($membership) {
            try {
                $membership = app(MembershipService::class)->verifyPaystack($data['reference']);
            } catch (\RuntimeException $e) {
                return $this->error($e->getMessage(), 422);
            }

            return $this->success([
                'type' => 'membership',
                'membership_id' => $membership->id,
                'plan' => $membership->plan->name,
                'expires_at' => $membership->expires_at,
            ], 'Membership activated.');
        }

        $group = $this->paymentService->verifyAndRecordPaystack($data['reference']);

        return $this->success([
            'order_group_id' => $group->id,
            'order_group_number' => $group->order_group_number,
            'payment_status' => $group->payment_status,
        ], 'Payment confirmed.');
    }

    public function verifyStripe(Request $request): JsonResponse
    {
        $data = $request->validate(['payment_intent_id' => 'required|string']);
        $group = $this->paymentService->verifyAndRecordStripe($data['payment_intent_id']);

        return $this->success([
            'order_group_id' => $group->id,
            'order_group_number' => $group->order_group_number,
            'payment_status' => $group->payment_status,
        ], 'Payment confirmed.');
    }
}
