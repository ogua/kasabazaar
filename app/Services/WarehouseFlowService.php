<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\EcommerceOrderStatus;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\PickupStation;
use App\Models\User;
use App\Notifications\Ecommerce\OrderUpdate;

/**
 * Vendors never deliver to buyers. Once an order is packed the vendor sends it to a
 * KASAROSE warehouse; we receive it, inspect it, and only then does it continue to the
 * customer (pickup station, delivery or KASAROSE shipping).
 *
 *   paid → processing → packed → sent_to_warehouse → at_warehouse → inspected
 *        → ready_for_pickup (pickup) | dispatched → in_transit (delivery) → delivered
 *   a failed inspection stops at inspection_failed and the item goes back to the vendor.
 */
class WarehouseFlowService
{
    /**
     * The vendor (or staff on their behalf) hands the packed order over to our warehouse.
     */
    public function sendToWarehouse(EcommerceOrder $order, ?PickupStation $warehouse, ?string $note, ?User $by): EcommerceOrder
    {
        $this->expect($order, [EcommerceOrderStatus::Packed], 'Only a packed order can be sent to the warehouse.');

        $order->update([
            'status' => EcommerceOrderStatus::SentToWarehouse->value,
            'warehouse_id' => $warehouse?->id,
            'sent_to_warehouse_at' => now(),
            'vendor_dispatch_note' => $note,
        ]);

        $this->record($order, EcommerceOrderStatus::SentToWarehouse, 'Sent by the vendor to our warehouse'.($warehouse ? " ({$warehouse->name})" : '').'.', $by);
        $this->tell($order, 'Order on its way to our warehouse', 'Your item is ready and the vendor is sending it to our warehouse, where we inspect it before it continues to you.');

        return $order;
    }

    public function receive(EcommerceOrder $order, PickupStation $warehouse, User $staff): EcommerceOrder
    {
        $this->expect($order, [EcommerceOrderStatus::SentToWarehouse, EcommerceOrderStatus::Packed], 'This order is not waiting to be received.');

        $order->update([
            'status' => EcommerceOrderStatus::AtWarehouse->value,
            'warehouse_id' => $warehouse->id,
            'received_at_warehouse_at' => now(),
        ]);

        $this->record($order, EcommerceOrderStatus::AtWarehouse, "Received at {$warehouse->name}. Awaiting inspection.", $staff);
        $this->tell($order, 'Order received at our warehouse', 'Your item has arrived at our warehouse and will be inspected shortly.');

        return $order;
    }

    public function inspect(EcommerceOrder $order, bool $passed, ?string $notes, User $staff): EcommerceOrder
    {
        $this->expect($order, [EcommerceOrderStatus::AtWarehouse], 'The order must be received at the warehouse before it is inspected.');

        $status = $passed ? EcommerceOrderStatus::Inspected : EcommerceOrderStatus::InspectionFailed;

        $order->update([
            'status' => $status->value,
            'inspected_at' => now(),
            'inspected_by' => $staff->id,
            'inspection_result' => $passed ? 'passed' : 'failed',
            'inspection_notes' => $notes,
        ]);

        if ($passed) {
            $this->record($order, $status, 'Inspection passed.'.($notes ? " {$notes}" : ''), $staff);
            $this->tell($order, 'Order inspected', 'Your item passed inspection. '.($order->delivery_method === DeliveryMethod::Pickup->value
                ? 'We will tell you when it is ready to collect.'
                : 'We will arrange its delivery to you next.'));
        } else {
            $this->record($order, $status, 'Inspection failed.'.($notes ? " {$notes}" : ''), $staff);
            $this->tell($order, 'Problem found at inspection', 'Your item did not pass our inspection'.($notes ? " ({$notes})" : '').'. We will return it to the vendor and contact you about a refund.');
        }

        return $order;
    }

    /**
     * Pickup orders: the inspected item is waiting at the customer's pickup station.
     */
    public function readyForPickup(EcommerceOrder $order, User $staff): EcommerceOrder
    {
        $this->expect($order, [EcommerceOrderStatus::Inspected], 'Only an inspected order can be made ready for pickup.');
        abort_unless($order->delivery_method === DeliveryMethod::Pickup->value, 422, 'This order is not a pickup order.');

        $order->update(['status' => EcommerceOrderStatus::ReadyForPickup->value, 'ready_for_pickup_at' => now()]);
        $station = $order->pickupStation;

        $this->record($order, EcommerceOrderStatus::ReadyForPickup, 'Ready for collection'.($station ? " at {$station->name}" : '').'.', $staff);
        $this->tell($order, 'Your order is ready for pickup', 'Your order is ready to collect'.($station ? " at {$station->name}, {$station->fullAddress()}" : '').'. Please bring your order number.');

        return $order;
    }

    /**
     * A failed-inspection item goes back to the vendor.
     */
    public function returnFailedToVendor(EcommerceOrder $order, User $staff): EcommerceOrder
    {
        $this->expect($order, [EcommerceOrderStatus::InspectionFailed], 'Only an order that failed inspection goes back to the vendor.');

        $order->update(['returned_to_vendor_at' => now()]);
        $this->record($order, EcommerceOrderStatus::InspectionFailed, 'Item returned to the vendor.', $staff);

        return $order;
    }

    /**
     * Outbound leg, from our warehouse to the customer. Never before inspection.
     */
    public function assertCanDispatchToCustomer(EcommerceOrder $order): void
    {
        $this->expect($order, [EcommerceOrderStatus::Inspected], 'The item must be inspected at our warehouse before it is dispatched to the customer.');
        abort_if($order->delivery_method === DeliveryMethod::Pickup->value, 422, 'Pickup orders are collected, not dispatched. Mark the order ready for pickup instead.');
    }

    /**
     * @param  array<int, EcommerceOrderStatus>  $allowed
     */
    private function expect(EcommerceOrder $order, array $allowed, string $message): void
    {
        abort_unless(in_array($order->status, $allowed, true), 422, $message);
    }

    private function record(EcommerceOrder $order, EcommerceOrderStatus $status, string $note, ?User $by): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status->value,
            'notes' => $note,
            'created_by' => $by?->id,
        ]);
    }

    private function tell(EcommerceOrder $order, string $title, string $body): void
    {
        $order->user->notify(new OrderUpdate($order->fresh('deliveryDetail'), $title, $body));
    }
}
