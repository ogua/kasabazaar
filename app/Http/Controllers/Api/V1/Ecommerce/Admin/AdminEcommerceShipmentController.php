<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceShipmentTrackingLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEcommerceShipmentController extends BaseApiController
{
    public function create(Request $request, string $orderId): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->findOrFail($orderId);

        abort_if($order->shipment !== null, 422, 'Shipment already exists for this order.');

        $data = $request->validate([
            'courier' => 'nullable|string|max:255',
            'delivery_person_id' => 'nullable|uuid|exists:staff,id',
            'estimated_delivery' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $trackingNumber = 'TRK-'.strtoupper(uniqid());

        $shipment = EcommerceOrderShipment::create(array_merge($data, [
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber,
            'status' => 'pending',
        ]));

        return $this->success([
            'id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'tracking_number' => $shipment->tracking_number,
            'courier' => $shipment->courier,
            'status' => $shipment->status,
            'estimated_delivery' => $shipment->estimated_delivery,
        ], 'Shipment created.', 201);
    }

    public function update(Request $request, string $orderId): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->with('shipment')->findOrFail($orderId);

        abort_if($order->shipment === null, 404, 'No shipment found for this order.');

        $data = $request->validate([
            'courier' => 'nullable|string|max:255',
            'delivery_person_id' => 'nullable|uuid|exists:staff,id',
            'estimated_delivery' => 'nullable|date',
            'status' => 'nullable|in:pending,assigned,picked_up,in_transit,delivered,failed',
            'notes' => 'nullable|string|max:500',
        ]);

        $order->shipment->update($data);

        return $this->success([
            'id' => $order->shipment->id,
            'tracking_number' => $order->shipment->tracking_number,
            'courier' => $order->shipment->courier,
            'status' => $order->shipment->status,
            'estimated_delivery' => $order->shipment->estimated_delivery,
        ], 'Shipment updated.');
    }

    public function addTracking(Request $request, string $orderId): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->with('shipment')->findOrFail($orderId);

        abort_if($order->shipment === null, 404, 'No shipment found for this order.');

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'status' => 'nullable|string|max:255',
            'recorded_at' => 'nullable|date',
        ]);

        $log = EcommerceShipmentTrackingLog::create([
            'order_shipment_id' => $order->shipment->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'status' => $data['status'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        if (isset($data['status'])) {
            $order->shipment->update(['status' => $data['status']]);

            if ($data['status'] === 'in_transit') {
                $order->update(['status' => \App\Enums\EcommerceOrderStatus::InTransit->value]);
            }
        }

        return $this->success([
            'id' => $log->id,
            'latitude' => $log->latitude,
            'longitude' => $log->longitude,
            'status' => $log->status,
            'recorded_at' => $log->recorded_at,
        ], 'Tracking log added.', 201);
    }
}
