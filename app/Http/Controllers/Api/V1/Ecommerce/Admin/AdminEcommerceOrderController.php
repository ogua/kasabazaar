<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Ecommerce\EcommerceOrderDetailResource;
use App\Http\Resources\Ecommerce\EcommerceOrderResource;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\ExchangeRateLog;
use App\Notifications\Ecommerce\EcommerceOrderApproved;
use App\Notifications\Ecommerce\EcommerceOrderDelivered;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\EcommerceOrderPacked;
use App\Notifications\Ecommerce\EcommerceOrderProcessing;
use App\Services\EcommerceInventoryService;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEcommerceOrderController extends BaseApiController
{
    public function __construct(
        protected EcommerceInventoryService $inventoryService,
        protected PushNotificationService $pushService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $query = EcommerceOrder::where('branch_id', $branchId)
            ->with('user:id,name,email')
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($o) => new EcommerceOrderResource($o));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $order = EcommerceOrder::where('branch_id', $branchId)
            ->with(['user', 'items.product.images', 'deliveryDetail', 'statusHistory.createdBy', 'shipment.trackingLogs', 'rating'])
            ->findOrFail($id);

        return $this->success(new EcommerceOrderDetailResource($order));
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Pending, 422, 'Order must be pending to approve.');

        $request->validate(['shipping_fee_ghs' => 'nullable|numeric|min:0']);
        $shippingFee = (float) $request->input('shipping_fee_ghs', 0);

        $rateLog = ExchangeRateLog::latest('created_at')->first();
        $rate = $rateLog?->rate ?? 1;

        $newTotal = $order->subtotal_ghs + $shippingFee - $order->discount_ghs;
        $newTotalUsd = $rate > 0 ? round($newTotal / $rate, 2) : null;

        $order->update([
            'status' => EcommerceOrderStatus::AwaitingPayment->value,
            'shipping_fee_ghs' => $shippingFee,
            'total_ghs' => $newTotal,
            'total_usd' => $newTotalUsd,
            'approved_by' => auth()->id(),
        ]);

        $this->appendHistory($order, EcommerceOrderStatus::AwaitingPayment->value, 'Approved by staff. Awaiting customer payment.');

        $user = $order->user;
        $user->notify(new EcommerceOrderApproved($order));
        $this->pushService->sendToUser($user->id, 'Order Approved', "Your order {$order->order_number} is approved. Please pay GHS {$order->total_ghs}.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(new EcommerceOrderDetailResource($order->load(['items', 'deliveryDetail', 'statusHistory'])), 'Order approved.');
    }

    public function process(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Paid, 422, 'Order must be paid to start processing.');

        $order->update(['status' => EcommerceOrderStatus::Processing->value]);
        $this->appendHistory($order, EcommerceOrderStatus::Processing->value, 'Order is being processed.');

        $user = $order->user;
        $user->notify(new EcommerceOrderProcessing($order));
        $this->pushService->sendToUser($user->id, 'Order Being Processed', "Order {$order->order_number} is being processed.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order moved to processing.');
    }

    public function pack(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Processing, 422, 'Order must be processing to pack.');

        $order->update(['status' => EcommerceOrderStatus::Packed->value]);
        $this->appendHistory($order, EcommerceOrderStatus::Packed->value, 'Order has been packed.');

        $user = $order->user;
        $user->notify(new EcommerceOrderPacked($order));
        $this->pushService->sendToUser($user->id, 'Order Packed', "Order {$order->order_number} has been packed.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order marked as packed.');
    }

    public function dispatch(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->with('shipment')->findOrFail($id);

        abort_unless($order->status === EcommerceOrderStatus::Packed, 422, 'Order must be packed to dispatch.');
        abort_unless($order->shipment !== null, 422, 'A shipment must be assigned before dispatching.');

        $order->update(['status' => EcommerceOrderStatus::Dispatched->value]);
        $order->shipment->update(['dispatched_at' => now()]);
        $this->appendHistory($order, EcommerceOrderStatus::Dispatched->value, 'Order dispatched.');

        $user = $order->user;
        $user->notify(new EcommerceOrderDispatched($order));
        $this->pushService->sendToUser($user->id, 'Order Dispatched', "Order {$order->order_number} is on its way.", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order dispatched.');
    }

    public function deliver(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->with('shipment')->findOrFail($id);
        $allowedStatus = [EcommerceOrderStatus::Dispatched, EcommerceOrderStatus::InTransit];

        abort_unless(in_array($order->status, $allowedStatus), 422, 'Order must be dispatched or in transit to deliver.');

        $order->update(['status' => EcommerceOrderStatus::Delivered->value]);

        if ($order->shipment) {
            $order->shipment->update(['delivered_at' => now(), 'status' => 'delivered']);
        }

        $this->appendHistory($order, EcommerceOrderStatus::Delivered->value, 'Order delivered to customer.');

        $user = $order->user;
        $user->notify(new EcommerceOrderDelivered($order));
        $this->pushService->sendToUser($user->id, 'Order Delivered', "Order {$order->order_number} has been delivered!", ['type' => 'ecommerce_order', 'order_id' => $order->id]);

        return $this->success(null, 'Order marked as delivered.');
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->with('items')->findOrFail($id);
        $nonCancellable = [EcommerceOrderStatus::Delivered, EcommerceOrderStatus::Refunded];

        abort_unless(! in_array($order->status, $nonCancellable), 422, 'Cannot cancel a delivered or refunded order.');

        $request->validate(['reason' => 'nullable|string|max:500']);

        $stockRestorableStatuses = [
            EcommerceOrderStatus::Pending, EcommerceOrderStatus::AwaitingPayment,
            EcommerceOrderStatus::Paid, EcommerceOrderStatus::Processing, EcommerceOrderStatus::Packed,
        ];

        if (in_array($order->status, $stockRestorableStatuses)) {
            $this->inventoryService->restoreForOrder($order, auth()->user());
        }

        $order->update([
            'status' => EcommerceOrderStatus::Cancelled->value,
            'cancelled_reason' => $request->reason,
            'cancelled_by' => auth()->id(),
        ]);

        $this->appendHistory($order, EcommerceOrderStatus::Cancelled->value, 'Cancelled by staff. '.$request->reason);

        return $this->success(null, 'Order cancelled.');
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $order = EcommerceOrder::where('branch_id', $branchId)->findOrFail($id);
        $allowedStatus = [EcommerceOrderStatus::Paid, EcommerceOrderStatus::Delivered];

        abort_unless(in_array($order->status, $allowedStatus), 422, 'Can only refund paid or delivered orders.');

        $order->update([
            'status' => EcommerceOrderStatus::Refunded->value,
            'payment_status' => EcommerceOrderPaymentStatus::Refunded->value,
        ]);

        $this->appendHistory($order, EcommerceOrderStatus::Refunded->value, 'Order refunded by staff.');

        return $this->success(null, 'Order marked as refunded. Process payment reversal in gateway dashboard.');
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
