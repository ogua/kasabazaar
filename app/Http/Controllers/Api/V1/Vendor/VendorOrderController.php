<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\EcommerceOrderStatus;
use App\Http\Resources\Ecommerce\EcommerceOrderDetailResource;
use App\Http\Resources\Ecommerce\EcommerceOrderResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\PickupStation;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Services\PushNotificationService;
use App\Services\WarehouseFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * Vendors do not deliver to buyers. A packed order is sent to one of our warehouses,
     * where it is received and inspected before it continues to the customer.
     */
    public function dispatch(Request $request, string $id): JsonResponse
    {
        $order = EcommerceOrder::where('vendor_id', $this->vendorId())->findOrFail($id);

        $data = $request->validate([
            'warehouse_id' => ['nullable', 'uuid', Rule::exists('pickup_stations', 'id')->where('is_active', true)->where('is_warehouse', true)],
            'note' => 'nullable|string|max:255',
        ]);

        app(WarehouseFlowService::class)->sendToWarehouse(
            $order,
            isset($data['warehouse_id']) ? PickupStation::find($data['warehouse_id']) : null,
            $data['note'] ?? null,
            $request->user()
        );

        return $this->success(null, 'Order sent to the warehouse.');
    }

    /**
     * The warehouses a vendor can deliver to.
     */
    public function warehouses(): JsonResponse
    {
        return $this->success(PickupStation::warehouses()->orderBy('sort_order')->orderBy('name')->get()->map(fn (PickupStation $w) => [
            'id' => $w->id,
            'name' => $w->name,
            'country' => $w->country,
            'address' => $w->fullAddress(),
            'phone' => $w->phone,
            'opening_hours' => $w->opening_hours,
            'instructions' => $w->instructions,
        ])->values());
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
