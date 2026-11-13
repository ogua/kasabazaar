<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\EcommerceOrderStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceOrder;
use App\Models\EcommerceOrderStatusHistory;
use App\Models\EcommerceProduct;
use App\Models\EcommerceReturn;
use App\Models\PickupStation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Notifications\Ecommerce\OrderUpdate;
use App\Services\EcommerceOrderService;
use App\Services\ReturnService;
use App\Services\WarehouseFlowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WarehouseFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $branch = Branch::create([
            'name' => 'Test Branch', 'country' => 'Ghana', 'state' => 'Greater Accra', 'address' => '1 Test Street',
            'email' => 'branch-'.uniqid().'@example.com', 'phone' => '0200000000', 'slug' => 'test-branch-'.uniqid(),
        ]);
        $client = Client::create(['branch_id' => $branch->id, 'name' => 'Ama', 'email' => 'customer-'.uniqid().'@example.com']);

        $this->customer = User::create([
            'name' => 'Ama', 'email' => $client->email, 'password' => bcrypt('password'), 'client_id' => $client->id,
            'branch_id' => $branch->id, 'role' => 'customer', 'status' => 'active',
        ]);
        $this->staff = User::create(['name' => 'Warehouse Lead', 'email' => 'wh-'.uniqid().'@example.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active']);
    }

    private function station(array $overrides = []): PickupStation
    {
        return PickupStation::create(array_merge([
            'name' => 'Accra Warehouse', 'country' => 'Ghana', 'city' => 'Accra', 'address' => '12 Airport Road',
            'is_pickup_point' => true, 'is_warehouse' => true, 'is_active' => true,
        ], $overrides));
    }

    private function orderIn(EcommerceOrderStatus $status, DeliveryMethod $method = DeliveryMethod::Delivery, ?PickupStation $pickup = null): EcommerceOrder
    {
        $owner = User::create(['name' => 'Vendor', 'email' => 'v-'.uniqid().'@example.com', 'password' => bcrypt('p'), 'role' => 'vendor', 'status' => 'active']);
        $vendor = Vendor::create(['user_id' => $owner->id, 'business_name' => 'Shop '.uniqid(), 'commission_rate' => 10, 'status' => 'active', 'approved_at' => now()]);
        $owner->update(['vendor_id' => $vendor->id]);
        VendorWallet::create(['vendor_id' => $vendor->id]);

        $product = EcommerceProduct::create([
            'branch_id' => $this->customer->branch_id, 'vendor_id' => $vendor->id, 'name' => 'Item '.uniqid(), 'slug' => 'item-'.uniqid(),
            'price_ghs' => 100, 'stock' => 10, 'weight' => 1, 'is_active' => true,
        ]);
        $address = DeliveryAddress::create(['user_id' => $this->customer->id, 'full_name' => 'Ama', 'phone' => '0200000000', 'country' => 'Ghana', 'region' => 'Greater Accra', 'city' => 'Accra', 'street' => '1 Test St']);
        // Earlier orders in the same test must not be handed back by the pending-checkout guard.
        \App\Models\EcommerceOrderGroup::where('user_id', $this->customer->id)->update(['payment_status' => 'paid']);
        $cart = EcommerceCart::firstOrCreate(['user_id' => $this->customer->id], ['expires_at' => now()->addDay()]);
        EcommerceCartItem::create(['cart_id' => $cart->id, 'ecommerce_product_id' => $product->id, 'vendor_id' => $vendor->id, 'quantity' => 1, 'price_ghs' => 100]);

        $order = app(EcommerceOrderService::class)->createFromCart($this->customer, $address->id, '', $method, $pickup?->id)->orders->first();
        $order->update(['status' => $status->value]);

        return $order->fresh();
    }

    public function test_a_pickup_order_remembers_the_station_the_customer_chose(): void
    {
        $station = $this->station(['name' => 'Kumasi Pickup', 'is_warehouse' => false]);
        $order = $this->orderIn(EcommerceOrderStatus::Paid, DeliveryMethod::Pickup, $station);

        $this->assertSame($station->id, $order->pickup_station_id);
        $this->assertSame('not_required', $order->delivery_fee_status);
    }

    public function test_checkout_needs_a_pickup_station_for_pickup_and_only_active_pickup_points(): void
    {
        $order = $this->orderIn(EcommerceOrderStatus::Paid);
        EcommerceOrder::query()->delete(); // keep the idempotent pending-group guard out of the way
        $warehouseOnly = $this->station(['is_pickup_point' => false]);
        $headers = ['Authorization' => 'Bearer '.$this->customer->createToken('t')->plainTextToken];
        $address = DeliveryAddress::where('user_id', $this->customer->id)->first();

        $this->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id, 'delivery_method' => 'pickup'], $headers)
            ->assertStatus(422)->assertJsonValidationErrors('pickup_station_id');

        $this->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id, 'delivery_method' => 'pickup', 'pickup_station_id' => $warehouseOnly->id], $headers)
            ->assertStatus(422)->assertJsonValidationErrors('pickup_station_id');
    }

    public function test_the_public_station_list_can_be_filtered_by_country(): void
    {
        $this->station(['name' => 'Accra Hub']);
        $this->station(['name' => 'Houston Hub', 'country' => 'USA', 'city' => 'Houston']);
        $this->station(['name' => 'Closed Hub', 'is_active' => false]);

        $usa = $this->getJson('/api/v1/marketplace/pickup-stations?country=USA')->assertOk()->json('data');
        $names = collect($usa)->pluck('name');

        $this->assertTrue($names->contains('Houston Hub'));
        $this->assertFalse($names->contains('Accra Hub'));
        $this->assertFalse(collect($this->getJson('/api/v1/marketplace/pickup-stations')->json('data'))->pluck('name')->contains('Closed Hub'));
    }

    public function test_vendors_send_to_the_warehouse_it_is_received_inspected_and_only_then_goes_on(): void
    {
        $warehouse = $this->station();
        $order = $this->orderIn(EcommerceOrderStatus::Packed);
        $flow = app(WarehouseFlowService::class);

        // Nothing goes to the customer before our inspection.
        try {
            $flow->assertCanDispatchToCustomer($order);
            $this->fail('Dispatch to the customer was allowed before inspection.');
        } catch (HttpException|HttpResponseException $e) {
            $this->assertStringContainsString('inspected', $e->getMessage());
        }

        $flow->sendToWarehouse($order, $warehouse, 'Courier XYZ', $this->staff);
        $this->assertSame(EcommerceOrderStatus::SentToWarehouse, $order->fresh()->status);

        $flow->receive($order->fresh(), $warehouse, $this->staff);
        $this->assertSame(EcommerceOrderStatus::AtWarehouse, $order->fresh()->status);

        $flow->inspect($order->fresh(), true, 'All good', $this->staff);
        $order->refresh();
        $this->assertSame(EcommerceOrderStatus::Inspected, $order->status);
        $this->assertSame('passed', $order->inspection_result);

        $flow->assertCanDispatchToCustomer($order); // now allowed
        Notification::assertSentToTimes($this->customer, OrderUpdate::class, 3);
        $this->assertGreaterThanOrEqual(3, EcommerceOrderStatusHistory::where('order_id', $order->id)->count());
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $flow = app(WarehouseFlowService::class);
        $paid = $this->orderIn(EcommerceOrderStatus::Paid);

        $this->expectException(HttpException::class);
        $flow->sendToWarehouse($paid, null, null, $this->staff); // not packed yet
    }

    public function test_a_pickup_order_becomes_ready_for_pickup_after_inspection_and_is_told_where(): void
    {
        $station = $this->station(['name' => 'Kumasi Pickup', 'city' => 'Kumasi', 'is_warehouse' => false]);
        $order = $this->orderIn(EcommerceOrderStatus::Inspected, DeliveryMethod::Pickup, $station);

        app(WarehouseFlowService::class)->readyForPickup($order, $this->staff);

        $this->assertSame(EcommerceOrderStatus::ReadyForPickup, $order->fresh()->status);
        Notification::assertSentTo($this->customer, OrderUpdate::class, fn (OrderUpdate $n) => str_contains($n->body, 'Kumasi Pickup'));
    }

    public function test_a_pickup_order_is_never_dispatched(): void
    {
        $station = $this->station(['is_warehouse' => false]);
        $order = $this->orderIn(EcommerceOrderStatus::Inspected, DeliveryMethod::Pickup, $station);

        $this->expectException(HttpException::class);
        app(WarehouseFlowService::class)->assertCanDispatchToCustomer($order);
    }

    public function test_a_failed_inspection_stops_the_order_and_the_item_goes_back_to_the_vendor(): void
    {
        $warehouse = $this->station();
        $order = $this->orderIn(EcommerceOrderStatus::AtWarehouse);
        $flow = app(WarehouseFlowService::class);

        $flow->inspect($order, false, 'Screen cracked', $this->staff);
        $this->assertSame(EcommerceOrderStatus::InspectionFailed, $order->fresh()->status);

        $flow->returnFailedToVendor($order->fresh(), $this->staff);
        $this->assertNotNull($order->fresh()->returned_to_vendor_at);
    }

    public function test_returns_only_open_on_a_delivered_order_inside_the_return_window(): void
    {
        $returns = app(ReturnService::class);

        $notDelivered = $this->orderIn(EcommerceOrderStatus::Paid);
        try {
            $returns->request($notDelivered, $this->customer, 'Changed my mind', null);
            $this->fail('A return was opened on an undelivered order.');
        } catch (HttpException $e) {
            $this->assertStringContainsString('delivered', $e->getMessage());
        }

        $old = $this->orderIn(EcommerceOrderStatus::Delivered);
        EcommerceOrderStatusHistory::create(['order_id' => $old->id, 'status' => 'delivered', 'notes' => 'Delivered', 'created_at' => now()->subDays(20)]);
        $this->expectException(HttpException::class);
        $returns->request($old, $this->customer, 'Too late', null);
    }

    public function test_a_return_comes_to_the_warehouse_and_is_checked_before_going_back_to_the_vendor(): void
    {
        $warehouse = $this->station(['name' => 'Accra Warehouse']);
        $order = $this->orderIn(EcommerceOrderStatus::Delivered);
        $returns = app(ReturnService::class);

        $return = $returns->request($order, $this->customer, 'Not as described', 'Colour is wrong');
        $this->assertSame(EcommerceReturn::REQUESTED, $return->status);
        $this->assertStringStartsWith('RTN-', $return->return_number);

        // A second open return on the same order is refused.
        try {
            $returns->request($order, $this->customer, 'Again', null);
            $this->fail('A second return was opened.');
        } catch (HttpException $e) {
            $this->assertStringContainsString('already open', $e->getMessage());
        }

        // It cannot go back to the vendor before it has arrived and been inspected.
        try {
            $returns->returnToVendor($return->fresh(), $this->staff);
            $this->fail('Return went back to the vendor unchecked.');
        } catch (HttpException) {
            $this->assertSame(EcommerceReturn::REQUESTED, $return->fresh()->status);
        }

        $returns->approve($return->fresh(), $warehouse, null, $this->staff);
        Notification::assertSentTo($this->customer, OrderUpdate::class, fn (OrderUpdate $n) => str_contains($n->body, 'Accra Warehouse'));

        $returns->receive($return->fresh(), $this->staff);
        $returns->inspect($return->fresh(), 'as_described', 'Genuine defect', $this->staff);
        $returns->returnToVendor($return->fresh(), $this->staff);

        $final = $return->fresh();
        $this->assertSame(EcommerceReturn::RETURNED_TO_VENDOR, $final->status);
        $this->assertNotNull($final->received_at);
        $this->assertNotNull($final->inspected_at);
        $this->assertNotNull($final->returned_to_vendor_at);

        $returns->refund($final, $this->staff);
        $this->assertSame(EcommerceOrderStatus::Refunded, $order->fresh()->status);
    }

    public function test_a_return_can_be_declined_before_the_item_is_sent(): void
    {
        $order = $this->orderIn(EcommerceOrderStatus::Delivered);
        $returns = app(ReturnService::class);
        $return = $returns->request($order, $this->customer, 'Do not like it', null);

        $returns->reject($return, 'Outside our policy', $this->staff);

        $this->assertSame(EcommerceReturn::REJECTED, $return->fresh()->status);
    }
}
