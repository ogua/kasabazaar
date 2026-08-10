<?php

namespace Database\Seeders;

use App\Enums\EcommerceOrderPaymentStatus;
use App\Enums\EcommerceOrderShipmentStatus;
use App\Enums\EcommerceOrderStatus;
use App\Models\DeliveryAddress;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceOrderRating;
use App\Models\EcommerceOrderShipment;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\EcommerceProduct;
use App\Models\EcommerceShipmentTrackingLog;
use App\Models\OrderDeliveryDetail;
use App\Models\User;
use Database\Seeders\Concerns\SeedsEcommerceDefaults;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EcommerceOrderSeeder extends Seeder
{
    use SeedsEcommerceDefaults;

    private const TARGET_ORDER_COUNT = 30;

    private const EXCHANGE_RATE = 15.5;

    /**
     * The linear happy-path progression of an order.
     *
     * @var array<int, EcommerceOrderStatus>
     */
    private array $linearFlow = [
        EcommerceOrderStatus::Pending,
        EcommerceOrderStatus::AwaitingPayment,
        EcommerceOrderStatus::Paid,
        EcommerceOrderStatus::Processing,
        EcommerceOrderStatus::Packed,
        EcommerceOrderStatus::Dispatched,
        EcommerceOrderStatus::InTransit,
        EcommerceOrderStatus::Delivered,
    ];

    /**
     * Weighted distribution of final order statuses to exercise every status/enum branch.
     *
     * @var array<string, int>
     */
    private array $statusWeights = [
        'pending' => 2,
        'awaiting_payment' => 2,
        'paid' => 2,
        'processing' => 2,
        'packed' => 2,
        'dispatched' => 2,
        'in_transit' => 3,
        'delivered' => 10,
        'cancelled' => 3,
        'refunded' => 2,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (EcommerceOrder::withTrashed()->count() >= self::TARGET_ORDER_COUNT) {
            return;
        }

        $branch = $this->ecommerceBranch();
        $admin = $this->ecommerceAdmin();
        $driver = $this->ecommerceDriver();
        $customers = $this->ecommerceCustomers();
        $products = EcommerceProduct::where('branch_id', $branch->id)->get();

        if ($products->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $sequence = EcommerceOrder::withTrashed()->count();
        $weightedStatuses = $this->expandWeightedStatuses();

        for ($i = 0; $i < self::TARGET_ORDER_COUNT; $i++) {
            $sequence++;

            $customer = $customers->random();
            $targetStatus = EcommerceOrderStatus::from(fake()->randomElement($weightedStatuses));
            $createdAt = Carbon::now()->subDays(fake()->numberBetween(1, 60))->subHours(fake()->numberBetween(0, 23));

            $this->createOrder($branch->id, $customer, $products, $targetStatus, $createdAt, $sequence, $admin, $driver);
        }
    }

    /**
     * @return array<int, string>
     */
    private function expandWeightedStatuses(): array
    {
        $expanded = [];

        foreach ($this->statusWeights as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $expanded[] = $status;
            }
        }

        return $expanded;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EcommerceProduct>  $products
     */
    private function createOrder(
        string $branchId,
        User $customer,
        $products,
        EcommerceOrderStatus $targetStatus,
        Carbon $createdAt,
        int $sequence,
        User $admin,
        $driver
    ): void {
        $orderProducts = $products->random(min(fake()->numberBetween(1, 4), $products->count()));
        $subtotal = 0.0;

        $order = EcommerceOrder::create([
            'order_number' => sprintf('KMB-%s-%04d', $createdAt->format('Ymd'), $sequence),
            'user_id' => $customer->id,
            'branch_id' => $branchId,
            'status' => $targetStatus,
            'subtotal_ghs' => 0,
            'shipping_fee_ghs' => fake()->randomElement([20, 30, 40, 50]),
            'discount_ghs' => 0,
            'total_ghs' => 0,
            'payment_status' => $this->paymentStatusFor($targetStatus),
            'payment_gateway' => fake()->randomElement(['paystack', 'mobile_money', 'cash_on_delivery']),
            'payment_reference' => $targetStatus !== EcommerceOrderStatus::Pending ? strtoupper(fake()->bothify('PSK-########')) : null,
            'coupon_code' => fake()->boolean(15) ? strtoupper(fake()->lexify('SAVE??')) : null,
            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
            'cancelled_reason' => $targetStatus === EcommerceOrderStatus::Cancelled ? fake()->randomElement([
                'Customer requested cancellation',
                'Item out of stock',
                'Duplicate order',
            ]) : null,
            'cancelled_by' => $targetStatus === EcommerceOrderStatus::Cancelled ? $admin->id : null,
            'approved_by' => in_array($targetStatus, [
                EcommerceOrderStatus::Paid,
                EcommerceOrderStatus::Processing,
                EcommerceOrderStatus::Packed,
                EcommerceOrderStatus::Dispatched,
                EcommerceOrderStatus::InTransit,
                EcommerceOrderStatus::Delivered,
                EcommerceOrderStatus::Refunded,
            ], true) ? $admin->id : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        foreach ($orderProducts as $product) {
            $quantity = fake()->numberBetween(1, 3);
            $unitPrice = (float) ($product->discount_price_ghs ?? $product->price_ghs);
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            EcommerceOrderItem::create([
                'order_id' => $order->id,
                'ecommerce_product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price_ghs' => $unitPrice,
                'total_ghs' => $lineTotal,
            ]);
        }

        $discount = fake()->boolean(15) ? round($subtotal * 0.1, 2) : 0;
        $total = round($subtotal + (float) $order->shipping_fee_ghs - $discount, 2);

        $order->update([
            'subtotal_ghs' => $subtotal,
            'discount_ghs' => $discount,
            'total_ghs' => $total,
            'total_usd' => round($total / self::EXCHANGE_RATE, 2),
            'exchange_rate' => self::EXCHANGE_RATE,
        ]);

        $this->seedDeliveryDetail($order, $customer, $createdAt);
        $timeline = $this->seedStatusHistory($order, $targetStatus, $createdAt, $admin);
        $this->seedShipment($order, $targetStatus, $timeline, $driver);
        $this->seedRating($order, $customer, $targetStatus, $timeline[EcommerceOrderStatus::Delivered->value] ?? null);
    }

    private function paymentStatusFor(EcommerceOrderStatus $status): EcommerceOrderPaymentStatus
    {
        return match ($status) {
            EcommerceOrderStatus::Pending, EcommerceOrderStatus::AwaitingPayment => EcommerceOrderPaymentStatus::Pending,
            EcommerceOrderStatus::Cancelled => fake()->randomElement([EcommerceOrderPaymentStatus::Pending, EcommerceOrderPaymentStatus::Failed]),
            EcommerceOrderStatus::Refunded => EcommerceOrderPaymentStatus::Refunded,
            default => EcommerceOrderPaymentStatus::Paid,
        };
    }

    private function seedDeliveryDetail(EcommerceOrder $order, User $customer, Carbon $createdAt): void
    {
        $existingAddress = DeliveryAddress::where('user_id', $customer->id)->first();
        $location = fake()->randomElement($this->ghanaLocations());

        OrderDeliveryDetail::create([
            'order_id' => $order->id,
            'full_name' => $existingAddress->full_name ?? $customer->name,
            'phone' => $existingAddress->phone ?? ($customer->phone ?? fake()->numerify('+2332#######')),
            'alternative_phone' => $existingAddress->alternative_phone ?? null,
            'email' => $customer->email,
            'country' => 'Ghana',
            'region' => $existingAddress->region ?? $location['region'],
            'city' => $existingAddress->city ?? fake()->randomElement($location['cities']),
            'suburb' => $existingAddress->suburb ?? fake()->citySuffix(),
            'street' => $existingAddress->street ?? fake()->streetName(),
            'house_number' => $existingAddress->house_number ?? (string) fake()->buildingNumber(),
            'digital_address' => $existingAddress->digital_address ?? (strtoupper(fake()->lexify('??')).'-'.fake()->numerify('###-####')),
            'landmark' => $existingAddress->landmark ?? null,
            'postal_code' => null,
            'latitude' => $existingAddress->latitude ?? fake()->latitude(5.0, 6.2),
            'longitude' => $existingAddress->longitude ?? fake()->longitude(-0.5, 0.2),
            'delivery_notes' => $existingAddress->delivery_notes ?? null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * Creates the status history trail leading up to the target status.
     *
     * @return array<string, Carbon> map of status value => timestamp reached
     */
    private function seedStatusHistory(EcommerceOrder $order, EcommerceOrderStatus $target, Carbon $createdAt, User $admin): array
    {
        $chain = match ($target) {
            EcommerceOrderStatus::Cancelled => [EcommerceOrderStatus::Pending, EcommerceOrderStatus::Cancelled],
            EcommerceOrderStatus::Refunded => [EcommerceOrderStatus::Pending, EcommerceOrderStatus::AwaitingPayment, EcommerceOrderStatus::Paid, EcommerceOrderStatus::Refunded],
            default => array_slice($this->linearFlow, 0, array_search($target, $this->linearFlow, true) + 1),
        };

        $timeline = [];
        $timestamp = $createdAt->copy();

        foreach ($chain as $index => $status) {
            if ($index > 0) {
                $timestamp = $timestamp->copy()->addHours(fake()->numberBetween(2, 20));
            }

            EcommerceOrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $status->value,
                'notes' => $this->statusNote($status),
                'created_by' => $index === 0 ? null : $admin->id,
                'created_at' => $timestamp,
            ]);

            $timeline[$status->value] = $timestamp->copy();
        }

        return $timeline;
    }

    private function statusNote(EcommerceOrderStatus $status): ?string
    {
        return match ($status) {
            EcommerceOrderStatus::Pending => 'Order placed by customer.',
            EcommerceOrderStatus::AwaitingPayment => 'Awaiting payment confirmation.',
            EcommerceOrderStatus::Paid => 'Payment received and confirmed.',
            EcommerceOrderStatus::Processing => 'Order is being prepared.',
            EcommerceOrderStatus::Packed => 'Order packed and ready for dispatch.',
            EcommerceOrderStatus::Dispatched => 'Order handed over to delivery driver.',
            EcommerceOrderStatus::InTransit => 'Order is on the way to the customer.',
            EcommerceOrderStatus::Delivered => 'Order delivered successfully.',
            EcommerceOrderStatus::Cancelled => 'Order was cancelled.',
            EcommerceOrderStatus::Refunded => 'Order refunded to customer.',
        };
    }

    /**
     * @param  array<string, Carbon>  $timeline
     */
    private function seedShipment(EcommerceOrder $order, EcommerceOrderStatus $target, array $timeline, $driver): void
    {
        $shipmentStatus = match ($target) {
            EcommerceOrderStatus::Packed => EcommerceOrderShipmentStatus::Assigned,
            EcommerceOrderStatus::Dispatched => EcommerceOrderShipmentStatus::PickedUp,
            EcommerceOrderStatus::InTransit => EcommerceOrderShipmentStatus::InTransit,
            EcommerceOrderStatus::Delivered => EcommerceOrderShipmentStatus::Delivered,
            default => null,
        };

        if ($shipmentStatus === null) {
            return;
        }

        $dispatchedAt = $timeline[EcommerceOrderStatus::Dispatched->value] ?? null;
        $deliveredAt = $timeline[EcommerceOrderStatus::Delivered->value] ?? null;

        $shipment = EcommerceOrderShipment::create([
            'order_id' => $order->id,
            'tracking_number' => 'KMB-TRK-'.strtoupper(fake()->bothify('??######')),
            'courier' => fake()->randomElement(['Kasabazaar Fleet', 'DHL Express', 'Speedaf']),
            'delivery_person_id' => $driver->id,
            'status' => $shipmentStatus,
            'estimated_delivery' => ($timeline[EcommerceOrderStatus::Packed->value] ?? $order->created_at)->copy()->addDays(3),
            'dispatched_at' => $dispatchedAt,
            'delivered_at' => $deliveredAt,
            'notes' => fake()->boolean(20) ? 'Customer prefers evening delivery.' : null,
        ]);

        if (in_array($shipmentStatus, [EcommerceOrderShipmentStatus::InTransit, EcommerceOrderShipmentStatus::Delivered], true)) {
            $this->seedTrackingLogs($shipment, $dispatchedAt ?? $order->created_at, $deliveredAt);
        }
    }

    private function seedTrackingLogs(EcommerceOrderShipment $shipment, Carbon $start, ?Carbon $end): void
    {
        $points = fake()->numberBetween(2, 4);
        $duration = $end ? max(1, $start->diffInMinutes($end)) : 120;
        $interval = intdiv($duration, $points + 1) ?: 1;

        // Roughly walk between two points around Accra for a believable GPS trail.
        $originLat = fake()->latitude(5.55, 5.70);
        $originLng = fake()->longitude(-0.30, -0.10);
        $destLat = fake()->latitude(5.55, 5.70);
        $destLng = fake()->longitude(-0.30, -0.10);

        for ($i = 1; $i <= $points; $i++) {
            $fraction = $i / ($points + 1);
            $recordedAt = $start->copy()->addMinutes($interval * $i);

            EcommerceShipmentTrackingLog::create([
                'order_shipment_id' => $shipment->id,
                'latitude' => $originLat + ($destLat - $originLat) * $fraction,
                'longitude' => $originLng + ($destLng - $originLng) * $fraction,
                'status' => $shipment->status->value,
                'recorded_at' => $recordedAt,
                'created_at' => $recordedAt,
                'updated_at' => $recordedAt,
            ]);
        }
    }

    private function seedRating(EcommerceOrder $order, User $customer, EcommerceOrderStatus $target, ?Carbon $deliveredAt): void
    {
        if ($target !== EcommerceOrderStatus::Delivered || ! fake()->boolean(60)) {
            return;
        }

        EcommerceOrderRating::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->boolean(70) ? fake()->sentence() : null,
            'created_at' => ($deliveredAt ?? now())->copy()->addHours(fake()->numberBetween(1, 48)),
        ]);
    }
}
