<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\EcommerceOrderStatus;
use App\Http\Resources\Ecommerce\EcommerceOrderDetailResource;
use App\Http\Resources\Ecommerce\EcommerceOrderResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorOrderController extends VendorBaseController
{
    public function __construct(protected PushNotificationService $pushService) {}

    public function index(Request $request): JsonResponse
    {
        $query = EcommerceOrder::where('vendor_id', $this->vendorId())
            ->with('user:id,name,email')
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($o) => new EcommerceOrderResource($o));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('vendor_id', $this->vendorId())
            ->with(['user', 'items.product.images', 'deliveryDetail', 'statusHistory', 'shipment.trackingLogs'])
            ->findOrFail($id);

        return $this->success(new EcommerceOrderDetailResource($order));
    }

    public function process(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('vendor_id', $this->vendorId())->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Paid, 422, 'Order must be paid to start processing.');

        $order->update(['status' => EcommerceOrderStatus::Processing->value]);
        $this->appendHistory($order, EcommerceOrderStatus::Processing->value, 'Order is being processed.');

        $order->user->notify(new EcommerceOrderProcessing($order));
        $this->pushService->sendToUser($order->user_id, 'Order Being Processed', "Order {$order->order_number} is being processed.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order moved to processing.');
    }

    public function pack(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('vendor_id', $this->vendorId())->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Processing, 422, 'Order must be processing to pack.');

        $order->update(['status' => EcommerceOrderStatus::Packed->value]);
        $this->appendHistory($order, EcommerceOrderStatus::Packed->value, 'Order has been packed.');

        $order->user->notify(new EcommerceOrderPacked($order));
        $this->pushService->sendToUser($order->user_id, 'Order Packed', "Order {$order->order_number} has been packed.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order marked as packed.');
    }

    public function dispatch(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('vendor_id', $this->vendorId())->with('shipment')->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Packed, 422, 'Order must be packed to dispatch.');
        abort_unless($order->shipment !== null, 422, 'A shipment must be assigned before dispatching.');

        $order->update(['status' => EcommerceOrderStatus::Dispatched->value]);
        $order->shipment->update(['dispatched_at' => now()]);
        $this->appendHistory($order, EcommerceOrderStatus::Dispatched->value, 'Order dispatched.');

        $order->user->notify(new EcommerceOrderDispatched($order));
        $this->pushService->sendToUser($order->user_id, 'Order Dispatched', "Order {$order->order_number} is on its way.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order dispatched.');
    }

    private function appendHistory(EcommerceOrder $order, string $status, string $notes = ''): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }
}
