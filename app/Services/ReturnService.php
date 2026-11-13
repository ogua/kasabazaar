<?php

namespace App\Services;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\EcommerceReturn;
use App\Models\PickupStation;
use App\Models\User;
use App\Notifications\Ecommerce\OrderUpdate;

/**
 * Returns never go straight back to the vendor: the customer brings or sends the item to
 * one of our warehouses, we check it, and only then does it go back to the vendor.
 *
 *   requested → approved → received (at warehouse) → inspected → returned_to_vendor
 *                                                              → refunded
 *   a request can be rejected before the item is sent.
 */
class ReturnService
{
    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [EcommerceReturn::REQUESTED, EcommerceReturn::APPROVED, EcommerceReturn::RECEIVED, EcommerceReturn::INSPECTED];
    }

    public function request(EcommerceOrder $order, User $user, string $reason, ?string $details): EcommerceReturn
    {
        abort_unless($order->status === EcommerceOrderStatus::Delivered, 422, 'Only a delivered order can be returned.');

        $deliveredAt = $order->statusHistory()->where('status', EcommerceOrderStatus::Delivered->value)->latest()->first()?->created_at ?? $order->updated_at;
        $windowDays = (int) config('ecommerce.return_window_days', 7);

        abort_if($deliveredAt->copy()->addDays($windowDays)->isPast(), 422, "The {$windowDays}-day return window for this order has closed.");
        abort_if(EcommerceReturn::where('order_id', $order->id)->whereIn('status', self::activeStatuses())->exists(), 422, 'A return is already open for this order.');

        $return = EcommerceReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'vendor_id' => $order->vendor_id,
            'status' => EcommerceReturn::REQUESTED,
            'reason' => $reason,
            'details' => $details,
        ]);

        $this->record($order, "Return {$return->return_number} requested by the customer: {$reason}", $user);
        $this->tell($return, 'Return request received', "We received your return request {$return->return_number}. We will review it and tell you where to bring or send the item.");

        return $return;
    }

    public function approve(EcommerceReturn $return, PickupStation $warehouse, ?string $notes, User $staff): EcommerceReturn
    {
        $this->expect($return, [EcommerceReturn::REQUESTED]);

        $return->update(['status' => EcommerceReturn::APPROVED, 'warehouse_id' => $warehouse->id, 'approved_at' => now(), 'staff_notes' => $notes]);

        $this->record($return->order, "Return {$return->return_number} approved. Customer to deliver the item to {$warehouse->name}.", $staff);
        $this->tell($return, 'Return approved', "Your return {$return->return_number} is approved. Please bring or send the item to {$warehouse->name}, {$warehouse->fullAddress()}"
            .($warehouse->phone ? " (tel. {$warehouse->phone})" : '').'. We check every return before it goes back to the vendor.');

        return $return;
    }

    public function reject(EcommerceReturn $return, string $reason, User $staff): EcommerceReturn
    {
        $this->expect($return, [EcommerceReturn::REQUESTED]);

        $return->update(['status' => EcommerceReturn::REJECTED, 'staff_notes' => $reason]);

        $this->record($return->order, "Return {$return->return_number} declined: {$reason}", $staff);
        $this->tell($return, 'Return request declined', "We could not approve return {$return->return_number}: {$reason}");

        return $return;
    }

    public function receive(EcommerceReturn $return, User $staff): EcommerceReturn
    {
        $this->expect($return, [EcommerceReturn::APPROVED]);

        $return->update(['status' => EcommerceReturn::RECEIVED, 'received_at' => now()]);

        $this->record($return->order, "Return {$return->return_number} received at {$return->warehouse?->name}.", $staff);
        $this->tell($return, 'Return received', "We have received your returned item for {$return->return_number} and will inspect it.");

        return $return;
    }

    public function inspect(EcommerceReturn $return, string $result, ?string $notes, User $staff): EcommerceReturn
    {
        $this->expect($return, [EcommerceReturn::RECEIVED]);

        $return->update(['status' => EcommerceReturn::INSPECTED, 'inspection_result' => $result, 'inspected_at' => now(), 'staff_notes' => $notes ?: $return->staff_notes]);

        $this->record($return->order, "Return {$return->return_number} inspected: {$result}.".($notes ? " {$notes}" : ''), $staff);

        return $return;
    }

    /**
     * After inspection the item goes back to the vendor.
     */
    public function returnToVendor(EcommerceReturn $return, User $staff): EcommerceReturn
    {
        $this->expect($return, [EcommerceReturn::INSPECTED]);

        $return->update(['status' => EcommerceReturn::RETURNED_TO_VENDOR, 'returned_to_vendor_at' => now()]);

        $this->record($return->order, "Return {$return->return_number} sent back to the vendor after inspection.", $staff);

        return $return;
    }

    /**
     * Refund the customer. The payment itself is reversed in the gateway dashboard.
     */
    public function refund(EcommerceReturn $return, User $staff): EcommerceReturn
    {
        abort_unless(in_array($return->status, [EcommerceReturn::INSPECTED, EcommerceReturn::RETURNED_TO_VENDOR], true), 422, 'A return is refunded after it has been inspected.');

        $return->update(['status' => EcommerceReturn::REFUNDED, 'refunded_at' => now()]);
        $return->order->update(['status' => EcommerceOrderStatus::Refunded->value, 'payment_status' => EcommerceOrderPaymentStatus::Refunded->value]);

        $this->record($return->order, "Return {$return->return_number} refunded.", $staff);
        $this->tell($return, 'Refund approved', "Your return {$return->return_number} passed our checks and your refund is being processed.");

        return $return;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function expect(EcommerceReturn $return, array $allowed): void
    {
        abort_unless(in_array($return->status, $allowed, true), 422, 'This return is not at the right stage for that action.');
    }

    private function record(EcommerceOrder $order, string $note, ?User $by): void
    {
        EcommerceOrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'notes' => $note,
            'created_by' => $by?->id,
        ]);
    }

    private function tell(EcommerceReturn $return, string $title, string $body): void
    {
        $return->user->notify(new OrderUpdate($return->order->loadMissing('deliveryDetail'), $title, $body));
    }
}
