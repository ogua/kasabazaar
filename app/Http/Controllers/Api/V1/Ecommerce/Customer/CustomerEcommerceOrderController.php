<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Enums\EcommerceOrderStatus;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceOrderDetailResource;
use App\Http\Resources\Ecommerce\EcommerceOrderResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderRating;
use App\Models\EcommerceOrderStatusHistory;
use App\Notifications\Ecommerce\EcommerceOrderCancelled;
use App\Services\EcommerceInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerEcommerceOrderController extends CustomerBaseController
{
    public function __construct(protected EcommerceInventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $query = EcommerceOrder::where('user_id', auth()->id())
            ->with('vendor:id,business_name')
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_group_id')) {
            $query->where('order_group_id', $request->order_group_id);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->paginated($paginated, fn ($o) => new EcommerceOrderResource($o));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('user_id', auth()->id())
            ->with(['items.product.images', 'deliveryDetail', 'statusHistory', 'shipment.trackingLogs', 'rating', 'vendor:id,business_name,slug'])
            ->findOrFail($id);

        return $this->success(new EcommerceOrderDetailResource($order));
    }

    public function tracking(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('user_id', auth()->id())
            ->with(['shipment.trackingLogs'])
            ->findOrFail($id);

        if ($order->shipment === null) {
            return $this->success(null, 'No shipment assigned yet.');
        }

        return $this->success([
            'tracking_number' => $order->shipment->tracking_number,
            'courier' => $order->shipment->courier,
            'status' => $order->shipment->status,
            'estimated_delivery' => $order->shipment->estimated_delivery,
            'dispatched_at' => $order->shipment->dispatched_at,
            'delivered_at' => $order->shipment->delivered_at,
            'tracking_logs' => $order->shipment->trackingLogs->map(fn ($log) => [
                'latitude' => $log->latitude,
                'longitude' => $log->longitude,
                'status' => $log->status,
                'recorded_at' => $log->recorded_at,
            ]),
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('user_id', auth()->id())->with('items')->findOrFail($id);
        $cancellableFrom = [EcommerceOrderStatus::Pending, EcommerceOrderStatus::AwaitingPayment];

        abort_unless(in_array($order->status, $cancellableFrom), 422, 'Order can no longer be cancelled at this stage.');

        $request->validate(['reason' => 'nullable|string|max:500']);

        $this->inventoryService->restoreForOrder($order, auth()->user());

        $order->update([
            'status' => EcommerceOrderStatus::Cancelled->value,
            'cancelled_reason' => $request->reason ?? 'Cancelled by customer.',
            'cancelled_by' => auth()->id(),
        ]);

        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => EcommerceOrderStatus::Cancelled->value,
            'notes' => 'Cancelled by customer.',
            'created_by' => auth()->id(),
        ]);

        auth()->user()->notify(new EcommerceOrderCancelled($order));

        return $this->success(null, 'Order cancelled.');
    }

    public function rate(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('user_id', auth()->id())->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Delivered, 422, 'Can only rate delivered orders.');
        abort_if(EcommerceOrderRating::where('order_id', $order->id)->exists(), 422, 'You have already rated this order.');

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        EcommerceOrderRating::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $this->success(null, 'Thank you for your rating!');
    }
}
