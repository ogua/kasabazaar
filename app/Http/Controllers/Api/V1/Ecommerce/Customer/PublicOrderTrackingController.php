<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Ecommerce\PublicOrderTrackingResource;
use App\Models\EcommerceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicOrderTrackingController extends BaseApiController
{
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => 'required|string',
            'contact' => 'required|string',
        ]);

        $order = EcommerceOrder::where('order_number', $data['order_number'])
            ->with(['deliveryDetail', 'user:id,name,email,phone', 'statusHistory', 'shipment.trackingLogs'])
            ->withCount('items')
            ->first();

        if (! $order || ! $this->contactMatches($order, $data['contact'])) {
            return $this->error("We couldn't find an order with that number and contact detail.", 404);
        }

        return $this->success(new PublicOrderTrackingResource($order));
    }

    private function contactMatches(EcommerceOrder $order, string $contact): bool
    {
        $normalizedPhone = preg_replace('/[\s\-]/', '', $contact);
        $normalizedEmail = strtolower(trim($contact));

        $candidates = array_filter([
            $order->deliveryDetail?->phone,
            $order->deliveryDetail?->alternative_phone,
            $order->deliveryDetail?->email,
            $order->user?->phone,
            $order->user?->email,
        ]);

        foreach ($candidates as $candidate) {
            if (preg_replace('/[\s\-]/', '', $candidate) === $normalizedPhone) {
                return true;
            }

            if (strtolower(trim($candidate)) === $normalizedEmail) {
                return true;
            }
        }

        return false;
    }
}
