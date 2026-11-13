<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\DeliveryMethod;
use App\Enums\ProductCondition;
use App\Exceptions\AuctionException;
use App\Models\Auction;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceProduct;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Notifications\Ecommerce\DeliveryFeePaid;
use App\Notifications\Ecommerce\DeliveryFeeQuoted;
use App\Notifications\Ecommerce\DeliveryPlanned;
use App\Services\AuctionService;
use App\Services\DeliveryFeeService;
use App\Services\DeliveryOptionsService;
use App\Services\EcommerceOrderService;
use App\Services\MembershipService;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipAndDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(MembershipPlanSeeder::class);
    }

    private function makeVendor(): Vendor
    {
        $owner = User::create([
            'name' => 'Vendor Owner',
            'email' => 'vendor-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $vendor = Vendor::create([
            'user_id' => $owner->id,
            'business_name' => 'Shop '.uniqid(),
            'commission_rate' => 10,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $owner->update(['vendor_id' => $vendor->id]);
        VendorWallet::create(['vendor_id' => $vendor->id]);

        return $vendor;
    }

    private function makeCustomer(): User
    {
        $branch = Branch::create([
            'name' => 'Test Branch',
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.uniqid().'@example.com',
            'phone' => '0200000000',
            'slug' => 'test-branch-'.uniqid(),
        ]);

        $client = Client::create(['branch_id' => $branch->id, 'name' => 'Ama', 'email' => 'customer-'.uniqid().'@example.com']);

        return User::create([
            'name' => 'Ama',
            'email' => $client->email,
            'password' => bcrypt('password'),
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    private function joinAs(User $user, string $tier): Membership
    {
        $plan = MembershipPlan::where('tier', $tier)->firstOrFail();
        $membership = Membership::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'status' => Membership::STATUS_PENDING,
            'amount_ghs' => $plan->price_ghs,
        ]);

        return app(MembershipService::class)->activate($membership);
    }

    /**
     * @return array{0: User, 1: DeliveryAddress}
     */
    private function cartWith(array $products, User $user): array
    {
        $address = DeliveryAddress::create([
            'user_id' => $user->id,
            'full_name' => 'Ama Mensah',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
            'street' => '1 Test Street',
        ]);

        $cart = EcommerceCart::create(['user_id' => $user->id, 'expires_at' => now()->addDay()]);

        foreach ($products as $product) {
            EcommerceCartItem::create([
                'cart_id' => $cart->id,
                'ecommerce_product_id' => $product->id,
                'vendor_id' => $product->vendor_id,
                'quantity' => 2,
                'price_ghs' => $product->price_ghs,
            ]);
        }

        return [$user, $address];
    }

    private function makeProduct(Vendor $vendor, User $owner, array $overrides = []): EcommerceProduct
    {
        return EcommerceProduct::create(array_merge([
            'branch_id' => $owner->branch_id,
            'vendor_id' => $vendor->id,
            'name' => 'Item '.uniqid(),
            'slug' => 'item-'.uniqid(),
            'price_ghs' => 100,
            'stock' => 10,
            'weight' => 2.5,
            'is_active' => true,
        ], $overrides));
    }

    public function test_new_listings_default_to_the_new_condition_and_the_condition_is_exposed(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeVendor(), $customer);

        $this->assertSame(ProductCondition::New, $product->fresh()->condition);

        $product->update(['condition' => ProductCondition::Refurbished]);
        $payload = (new \App\Http\Resources\Ecommerce\EcommerceProductResource($product->fresh()))->toArray(request());

        $this->assertSame('refurbished', $payload['condition']);
        $this->assertSame('Refurbished', $payload['condition_label']);
    }

    public function test_kasarose_shipping_is_priced_by_weight_with_a_minimum_charge(): void
    {
        config(['ecommerce.kasarose_shipping' => ['rate_per_kg_usd' => 10, 'min_charge_usd' => 15, 'default_weight_kg' => 1, 'min_days' => 14, 'max_days' => 28]]);
        $service = app(DeliveryOptionsService::class);

        $heavy = $service->kasaroseQuote([['weight' => 2.5, 'quantity' => 2]]);
        $this->assertSame(5.0, $heavy['weight_kg']);
        $this->assertSame(50.0, $heavy['fee_usd']);

        $light = $service->kasaroseQuote([['weight' => 0.5, 'quantity' => 1]]);
        $this->assertSame(15.0, $light['fee_usd']);

        $unweighed = $service->kasaroseQuote([['weight' => null, 'quantity' => 3]]);
        $this->assertSame(3.0, $unweighed['weight_kg']);
    }

    public function test_usa_items_are_split_into_a_kasarose_order_and_local_items_follow_the_chosen_method(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor();
        $local = $this->makeProduct($vendor, $customer);
        $usa = $this->makeProduct($vendor, $customer, ['ships_from_usa' => true]);
        [$user, $address] = $this->cartWith([$local, $usa], $customer);

        $options = app(DeliveryOptionsService::class)->optionsFor(EcommerceCart::where('user_id', $user->id)->first()->items, $address);
        $byKey = collect($options['methods'])->keyBy('key');

        $this->assertSame(2, $options['total_items']);
        $this->assertSame(1, $byKey['delivery']['items_covered']);
        $this->assertSame(2, $byKey['kasarose_shipping']['items_covered']);
        $this->assertSame(10.0, $byKey['kasarose_shipping']['weight_kg']);
        $this->assertTrue($byKey['kasarose_shipping']['selectable']);
        // The cost of Delivery and KASAROSE shipping is quoted by staff, so none is shown to the buyer.
        $this->assertSame('quoted', $byKey['delivery']['fee_mode']);
        $this->assertSame('quoted', $byKey['kasarose_shipping']['fee_mode']);
        $this->assertSame('free', $byKey['pickup']['fee_mode']);

        $group = app(EcommerceOrderService::class)->createFromCart($user, $address->id, '', DeliveryMethod::Pickup);

        $methods = $group->orders->pluck('delivery_method')->sort()->values()->all();
        $this->assertSame(['kasarose_shipping', 'pickup'], $methods);

        $pickup = $group->orders->firstWhere('delivery_method', 'pickup');
        $this->assertEquals(0, $pickup->shipping_fee_ghs);
        $this->assertSame('not_required', $pickup->delivery_fee_status);

        $kasarose = $group->orders->firstWhere('delivery_method', 'kasarose_shipping');
        $this->assertEquals(0, $kasarose->shipping_fee_ghs); // nothing charged at checkout
        $this->assertSame('awaiting_quote', $kasarose->delivery_fee_status);
        $this->assertEquals(5.0, (float) $kasarose->shipping_weight_kg);
    }

    public function test_paying_dues_activates_a_membership_and_renewing_stacks_the_time(): void
    {
        $user = $this->makeCustomer();

        $first = $this->joinAs($user, 'standard');
        $this->assertTrue($first->isRunning());
        $this->assertEqualsWithDelta(now()->addMonths(12)->timestamp, $first->expires_at->timestamp, 5);

        $renewal = $this->joinAs($user, 'standard');
        $this->assertEqualsWithDelta(now()->addMonths(24)->timestamp, $renewal->expires_at->timestamp, 5);
        $this->assertSame(Membership::STATUS_EXPIRED, $first->fresh()->status);
        $this->assertSame($renewal->id, app(MembershipService::class)->activeFor($user)->id);
    }

    public function test_the_member_discount_comes_off_the_order(): void
    {
        $customer = $this->makeCustomer();
        $this->joinAs($customer, 'economic'); // 8% on the seeded plans
        $product = $this->makeProduct($this->makeVendor(), $customer);
        [$user, $address] = $this->cartWith([$product], $customer);

        $group = app(EcommerceOrderService::class)->createFromCart($user, $address->id);

        $this->assertEquals(16.0, (float) $group->membership_discount_ghs); // 8% of 2 x GHS 100
        $this->assertEquals(16.0, (float) $group->discount_ghs);
    }

    public function test_exclusive_auctions_need_a_membership_of_the_required_tier(): void
    {
        $service = app(MembershipService::class);
        $user = $this->makeCustomer();

        $this->assertTrue($service->meetsTier($user, null));
        $this->assertFalse($service->meetsTier($user, 'economic'));

        $this->joinAs($user, 'standard');
        $this->assertFalse($service->meetsTier($user, 'economic'));
        $this->assertTrue($service->meetsTier($user, 'standard'));

        $this->joinAs($user, 'executive');
        $this->assertTrue($service->meetsTier($user, 'economic'));
    }

    public function test_bidding_on_a_members_only_auction_is_refused_without_the_tier(): void
    {
        $auction = Auction::create([
            'vendor_id' => $this->makeVendor()->id,
            'title' => 'Members lot '.uniqid(),
            'starting_price_ghs' => 100,
            'bid_increment_ghs' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'extension_minutes' => 2,
            'status' => AuctionStatus::Published,
            'members_only_tier' => 'economic',
        ]);
        $bidder = $this->makeCustomer();

        $this->expectException(AuctionException::class);
        $this->expectExceptionMessage('Economic members and above');

        app(AuctionService::class)->placeBid($auction, $bidder, 100);
    }

    public function test_the_buyer_can_choose_kasarose_shipping_for_items_held_in_ghana(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeVendor(), $customer);
        [$user, $address] = $this->cartWith([$product], $customer);

        $group = app(EcommerceOrderService::class)->createFromCart($user, $address->id, '', DeliveryMethod::KasaroseShipping);

        $order = $group->orders->first();
        $this->assertSame('kasarose_shipping', $order->delivery_method);
        $this->assertEquals(5.0, (float) $order->shipping_weight_kg);
        $this->assertSame('awaiting_quote', $order->delivery_fee_status);
    }

    public function test_staff_quote_the_delivery_cost_the_customer_is_notified_pays_it_and_delivery_is_planned(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeVendor(), $customer);
        [$user, $address] = $this->cartWith([$product], $customer);
        $order = app(EcommerceOrderService::class)->createFromCart($user, $address->id, '', DeliveryMethod::Delivery)->orders->first();
        $staff = User::create(['name' => 'Shipping Lead', 'email' => 'staff-'.uniqid().'@example.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active']);
        config(['ecommerce.checkout_callback_urls.web' => 'https://market.test/checkout/callback']);
        $fees = app(DeliveryFeeService::class);

        // The customer cannot pay before a cost has been set.
        try {
            $fees->initiate($order->fresh(), $user);
            $this->fail('Payment started with no quote.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('no delivery cost', $e->getMessage());
        }

        $fees->quote($order->fresh(), 45.5, $staff, 'Bulky item');
        $order->refresh();
        $this->assertSame('quoted', $order->delivery_fee_status);
        $this->assertEquals(45.5, (float) $order->delivery_fee_ghs);
        Notification::assertSentTo($user, DeliveryFeeQuoted::class);

        Http::fake([
            '*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://pay.test/x', 'reference' => 'ref-fee-1', 'access_code' => 'a']]),
            '*/transaction/verify/ref-fee-1' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 4550, 'metadata' => ['order_id' => $order->id]]]),
        ]);

        $checkout = $fees->initiate($order, $user);
        $this->assertSame('https://pay.test/x', $checkout['authorization_url']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/transaction/initialize') && $request['amount'] === 4550);

        $fees->verifyPaystack('ref-fee-1');
        $fees->verifyPaystack('ref-fee-1'); // the webhook repeats the redirect: no double notification
        $this->assertSame('paid', $order->fresh()->delivery_fee_status);
        Notification::assertSentToTimes($user, DeliveryFeePaid::class, 1);

        $fees->planDelivery($order->fresh(), $staff, now()->addDays(2), 'Call before arriving');
        Notification::assertSentTo($user, DeliveryPlanned::class);
        $this->assertNotNull($order->fresh()->delivery_contacted_at);
    }

    public function test_a_payment_below_the_quoted_cost_is_rejected(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeProduct($this->makeVendor(), $customer);
        [$user, $address] = $this->cartWith([$product], $customer);
        $order = app(EcommerceOrderService::class)->createFromCart($user, $address->id)->orders->first();
        $staff = User::create(['name' => 'Lead', 'email' => 'lead-'.uniqid().'@example.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active']);
        app(DeliveryFeeService::class)->quote($order->fresh(), 80, $staff);

        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 1000, 'metadata' => ['order_id' => $order->id]]])]);

        $this->expectExceptionMessage('does not match');
        app(DeliveryFeeService::class)->verifyPaystack('ref-short');
    }
}
