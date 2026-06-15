<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Enums\EcommerceOrderStatus;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\EcommerceOrder;
use App\Services\EcommercePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommercePaymentController extends CustomerBaseController
{
    public function __construct(protected EcommercePaymentService $paymentService) {}

    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate(['order_id' => 'required|uuid']);
        $order = EcommerceOrder::where('user_id', auth()->id())->with('deliveryDetail')->findOrFail($data['order_id']);

        abort_unless($order->status === EcommerceOrderStatus::AwaitingPayment, 422, 'Order is not ready for payment.');

        $country = $order->deliveryDetail?->country ?? 'Ghana';
        $gateway = $this->paymentService->getGateway($country);

        $result = $gateway === 'paystack'
            ? $this->paymentService->initiatePaystack($order, auth()->user())
            : $this->paymentService->initiateStripe($order, auth()->user());

        return $this->success(array_merge(['gateway' => $gateway], $result));
    }

    public function verifyPaystack(Request $request): JsonResponse
    {
        $data = $request->validate(['reference' => 'required|string']);
        $order = $this->paymentService->verifyAndRecordPaystack($data['reference']);

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
        ], 'Payment confirmed.');
    }

    public function verifyStripe(Request $request): JsonResponse
    {
        $data = $request->validate(['payment_intent_id' => 'required|string']);
        $order = $this->paymentService->verifyAndRecordStripe($data['payment_intent_id']);

        return $this->success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
        ], 'Payment confirmed.');
    }
}
