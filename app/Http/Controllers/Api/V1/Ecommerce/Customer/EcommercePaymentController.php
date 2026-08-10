<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\EcommerceOrderGroup;
use App\Services\EcommercePaymentService;
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
