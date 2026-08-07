<?php

namespace App\Services;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCart;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderGroup;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\EcommerceProduct;
use App\Models\ExchangeRateLog;
use App\Models\OrderDeliveryDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\Ecommerce\EcommerceOrderPlaced;
use App\Notifications\Ecommerce\VendorNewOrderReceived;
use Illuminate\Support\Facades\DB;

class EcommerceOrderService
{
    public function __construct(
        protected EcommerceInventoryService $inventoryService,
        protected TaxService $taxService,
        protected ShippingService $shippingService,
        protected CouponService $couponService,
        protected PushNotificationService $pushService
    ) {}

    public function createFromCart(User $user, string $deliveryAddressId, string $notes = ''): EcommerceOrderGroup
    {
        $address = DeliveryAddress::where('user_id', $user->id)->findOrFail($deliveryAddressId);

        $cart = EcommerceCart::where('user_id', $user->id)->with('items.product.vendor')->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty.');
        }

        // Idempotent checkout guard (gap D.7): if the payment session dropped and
        // the shopper retries, reuse the still-pending group instead of creating
        // a duplicate order/stock deduction.
        $existingGroup = EcommerceOrderGroup::where('user_id', $user->id)
            ->where('payment_status', EcommerceOrderPaymentStatus::Pending->value)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        if ($existingGroup) {
            return $existingGroup->load(['orders.items', 'orders.deliveryDetail', 'orders.statusHistory', 'orders.vendor']);
        }

        foreach ($cart->items as $item) {
            $product = EcommerceProduct::find($item->ecommerce_product_id);

            if (! $product || $product->trashed()) {
                throw new \RuntimeException("Product '{$item->product?->name}' is no longer available.");
            }

            if (! $product->vendor_id) {
                throw new \RuntimeException("Product '{$product->name}' is not assigned to a vendor and cannot be ordered.");
            }

            if ($product->stock < $item->quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock: {$product->name} has only {$product->stock} units available."
                );
            }
        }

        $rateLog = ExchangeRateLog::latest('created_at')->first();
        $rate = $rateLog?->rate ?? 1;

        return DB::transaction(function () use ($user, $cart, $address, $notes, $rate) {
            $itemsByVendor = $cart->items->groupBy(fn ($item) => $item->product->vendor_id);
            $cartSubtotal = $cart->items->sum(fn ($item) => (float) $item->price_ghs * $item->quantity);

            [$coupon, $couponDiscountTotal] = $this->resolveCoupon($cart->coupon_code, $user, $cartSubtotal);

            $group = EcommerceOrderGroup::create([
                'user_id' => $user->id,
                'subtotal_ghs' => 0,
                'total_ghs' => 0,
                'exchange_rate' => $rate,
                'payment_status' => EcommerceOrderPaymentStatus::Pending->value,
                'coupon_code' => $coupon?->code,
                'notes' => $notes,
            ]);

            $totals = ['subtotal' => 0, 'shipping' => 0, 'tax' => 0, 'discount' => 0, 'total' => 0];

            foreach ($itemsByVendor as $vendorId => $vendorItems) {
                $vendor = Vendor::findOrFail($vendorId);

                $order = $this->createVendorOrder(
                    $group,
                    $vendor,
                    $vendorItems,
                    $address,
                    $notes,
                    $rate,
                    $cartSubtotal,
                    $couponDiscountTotal,
                    $coupon
                );

                $totals['subtotal'] += $order->subtotal_ghs;
                $totals['shipping'] += $order->shipping_fee_ghs;
                $totals['tax'] += $order->tax_ghs;
                $totals['discount'] += $order->discount_ghs;
                $totals['total'] += $order->total_ghs;

                $this->inventoryService->deductForOrder($order->load('items'), $user);

                EcommerceOrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => EcommerceOrderStatus::AwaitingPayment->value,
                    'notes' => 'Order placed by customer, awaiting payment.',
                    'created_by' => $user->id,
                ]);

                $user->notify(new EcommerceOrderPlaced($order));
                $vendor->user?->notify(new VendorNewOrderReceived($order));
            }

            $group->update([
                'subtotal_ghs' => $totals['subtotal'],
                'shipping_fee_ghs' => $totals['shipping'],
                'tax_ghs' => $totals['tax'],
                'discount_ghs' => $totals['discount'],
                'total_ghs' => $totals['total'],
                'total_usd' => $rate > 0 ? round($totals['total'] / $rate, 2) : null,
            ]);

            if ($coupon) {
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'order_group_id' => $group->id,
                    'discount_amount_ghs' => $totals['discount'],
                ]);
                $coupon->increment('used_count');
            }

            $cart->items()->delete();
            $cart->update(['coupon_code' => null, 'discount_ghs' => 0]);

            $this->pushService->sendToUser(
                $user->id,
                'Order Placed',
                "Your order {$group->order_group_number} has been placed.",
                ['type' => 'ecommerce_order_group', 'order_group_id' => $group->id]
            );

            return $group->load(['orders.items', 'orders.deliveryDetail', 'orders.statusHistory', 'orders.vendor']);
        });
    }

    /**
     * @return array{0: ?Coupon, 1: float} the coupon (if still valid) and the total discount across the whole cart.
     */
    private function resolveCoupon(?string $code, User $user, float $cartSubtotal): array
    {
        if (! $code) {
            return [null, 0.0];
        }

        try {
            $result = $this->couponService->validate($code, $user, $cartSubtotal);

            return [$result['coupon'], $result['discount_ghs']];
        } catch (\RuntimeException) {
            // Coupon became invalid between cart-apply and checkout (expired, limit
            // reached) — drop it silently rather than blocking checkout entirely.
            return [null, 0.0];
        }
    }

    private function createVendorOrder(
        EcommerceOrderGroup $group,
        Vendor $vendor,
        \Illuminate\Support\Collection $vendorItems,
        DeliveryAddress $address,
        string $notes,
        float $rate,
        float $cartSubtotal,
        float $couponDiscountTotal,
        ?Coupon $coupon
    ): EcommerceOrder {
        $order = EcommerceOrder::create([
            'order_group_id' => $group->id,
            'user_id' => $group->user_id,
            'vendor_id' => $vendor->id,
            'branch_id' => $vendor->branch_id,
            'status' => EcommerceOrderStatus::AwaitingPayment->value,
            'payment_status' => EcommerceOrderPaymentStatus::Pending->value,
            'subtotal_ghs' => 0,
            'total_ghs' => 0,
            'exchange_rate' => $rate,
            'notes' => $notes,
            'coupon_code' => $coupon?->code,
        ]);

        $vendorSubtotal = 0;

        foreach ($vendorItems as $cartItem) {
            $product = EcommerceProduct::lockForUpdate()->findOrFail($cartItem->ecommerce_product_id);
            $unitPrice = $product->discount_price_ghs ?? $product->price_ghs;
            $lineTotal = round($unitPrice * $cartItem->quantity, 2);
            $vendorSubtotal += $lineTotal;

            EcommerceOrderItem::create([
                'order_id' => $order->id,
                'ecommerce_product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $cartItem->quantity,
                'unit_price_ghs' => $unitPrice,
                'total_ghs' => $lineTotal,
            ]);
        }

        $vendorShipping = $this->shippingService->feeFor($vendor, $address, $vendorSubtotal);
        $vendorTax = $this->taxService->calculate($vendorSubtotal);
        $vendorDiscount = $cartSubtotal > 0 && $couponDiscountTotal > 0
            ? round($couponDiscountTotal * ($vendorSubtotal / $cartSubtotal), 2)
            : 0;

        $vendorTotal = round($vendorSubtotal + $vendorShipping + $vendorTax - $vendorDiscount, 2);

        $order->update([
            'subtotal_ghs' => $vendorSubtotal,
            'shipping_fee_ghs' => $vendorShipping,
            'tax_ghs' => $vendorTax,
            'discount_ghs' => $vendorDiscount,
            'total_ghs' => $vendorTotal,
            'total_usd' => $rate > 0 ? round($vendorTotal / $rate, 2) : null,
        ]);

        OrderDeliveryDetail::create([
            'order_id' => $order->id,
            'full_name' => $address->full_name,
            'phone' => $address->phone,
            'alternative_phone' => $address->alternative_phone,
            'email' => $address->email,
            'country' => $address->country,
            'region' => $address->region,
            'city' => $address->city,
            'suburb' => $address->suburb,
            'street' => $address->street,
            'house_number' => $address->house_number,
            'digital_address' => $address->digital_address,
            'landmark' => $address->landmark,
            'postal_code' => $address->postal_code,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'delivery_notes' => $address->delivery_notes,
        ]);

        return $order;
    }
}
