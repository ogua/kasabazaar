<?php

namespace App\Services;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCart;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\EcommerceProduct;
use App\Models\ExchangeRateLog;
use App\Models\OrderDeliveryDetail;
use App\Models\User;
use App\Notifications\Ecommerce\EcommerceOrderPlaced;
use Illuminate\Support\Facades\DB;

class EcommerceOrderService
{
    public function __construct(
        protected EcommerceInventoryService $inventoryService,
        protected PushNotificationService $pushService
    ) {}

    public function createFromCart(User $user, string $deliveryAddressId, string $notes = ''): EcommerceOrder
    {
        $address = DeliveryAddress::where('user_id', $user->id)->findOrFail($deliveryAddressId);

        $cart = EcommerceCart::where('user_id', $user->id)->with('items.product')->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty.');
        }

        // Validate live stock before creating anything
        foreach ($cart->items as $item) {
            $product = EcommerceProduct::find($item->ecommerce_product_id);

            if (! $product || $product->trashed()) {
                throw new \RuntimeException("Product '{$item->product?->name}' is no longer available.");
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
            $subtotalGhs = 0;

            $order = EcommerceOrder::create([
                'user_id' => $user->id,
                'branch_id' => $user->branch_id,
                'status' => EcommerceOrderStatus::Pending->value,
                'payment_status' => EcommerceOrderPaymentStatus::Pending->value,
                'subtotal_ghs' => 0,
                'total_ghs' => 0,
                'exchange_rate' => $rate,
                'notes' => $notes,
            ]);

            foreach ($cart->items as $cartItem) {
                $product = EcommerceProduct::lockForUpdate()->findOrFail($cartItem->ecommerce_product_id);
                $unitPrice = $product->price_ghs;
                $lineTotal = round($unitPrice * $cartItem->quantity, 2);
                $subtotalGhs += $lineTotal;

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

            $totalGhs = $subtotalGhs;
            $totalUsd = $rate > 0 ? round($totalGhs / $rate, 2) : null;

            $order->update([
                'subtotal_ghs' => $subtotalGhs,
                'total_ghs' => $totalGhs,
                'total_usd' => $totalUsd,
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

            $order->load('items');
            $this->inventoryService->deductForOrder($order, $order->user);

            EcommerceOrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => EcommerceOrderStatus::Pending->value,
                'notes' => 'Order placed by customer.',
                'created_by' => $order->user_id,
            ]);

            $cart->items()->delete();

            $user->notify(new EcommerceOrderPlaced($order));

            $this->pushService->sendToUser(
                $user->id,
                'Order Placed',
                "Your order {$order->order_number} has been placed.",
                ['type' => 'ecommerce_order', 'order_id' => $order->id]
            );

            return $order->load(['items', 'deliveryDetail', 'statusHistory']);
        });
    }
}
