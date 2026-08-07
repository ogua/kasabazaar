<?php

namespace Tests\Feature;

use App\Enums\VendorTransactionType;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\DeliveryAddress;
use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceProduct;
use App\Models\EcommerceVendorApplication;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Services\EcommercePaymentService;
use App\Services\VendorProvisioningService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiVendorCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    private function makeBranch(): Branch
    {
        return Branch::create([
            'name' => 'Test Branch',
            'country' => 'Ghana',
            'state' => 'Greater Accra',
            'address' => '1 Test Street',
            'email' => 'branch-'.uniqid().'@example.com',
            'phone' => '0200000000',
            'slug' => 'test-branch-'.uniqid(),
        ]);
    }

    private function makeVendor(string $businessName, float $commissionRate = 10.0): Vendor
    {
        $vendorUser = User::create([
            'name' => $businessName.' Owner',
            'email' => Str::slug($businessName).'-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'status' => 'active',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => $businessName,
            'commission_rate' => $commissionRate,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $vendorUser->update(['vendor_id' => $vendor->id]);
        VendorWallet::create(['vendor_id' => $vendor->id]);

        return $vendor;
    }

    private function makeCustomer(): User
    {
        $branch = $this->makeBranch();

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Test Customer',
            'email' => 'customer-'.uniqid().'@example.com',
        ]);

        return User::create([
            'name' => 'Test Customer',
            'email' => $client->email,
            'password' => bcrypt('password'),
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    private function makeProduct(Vendor $vendor, float $priceGhs, int $stock = 50): EcommerceProduct
    {
        return EcommerceProduct::create([
            'vendor_id' => $vendor->id,
            'name' => 'Product '.uniqid(),
            'price_ghs' => $priceGhs,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_multi_vendor_cart_splits_into_separate_orders_per_vendor_at_checkout(): void
    {
        $customer = $this->makeCustomer();
        $vendorA = $this->makeVendor('Vendor A', commissionRate: 10);
        $vendorB = $this->makeVendor('Vendor B', commissionRate: 20);

        $productA = $this->makeProduct($vendorA, 100.00);
        $productB = $this->makeProduct($vendorB, 50.00);

        $address = DeliveryAddress::create([
            'user_id' => $customer->id,
            'full_name' => 'Test Customer',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
            'is_default' => true,
        ]);

        $cart = EcommerceCart::create(['user_id' => $customer->id]);
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $productA->id,
            'vendor_id' => $vendorA->id,
            'quantity' => 2,
            'price_ghs' => 100.00,
        ]);
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $productB->id,
            'vendor_id' => $vendorB->id,
            'quantity' => 1,
            'price_ghs' => 50.00,
        ]);

        $response = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id]);

        $response->assertCreated();
        $response->assertJsonCount(2, 'data.orders');
        $response->assertJsonPath('data.subtotal_ghs', '250.00');

        $vendorIds = collect($response->json('data.orders'))->pluck('vendor.id')->all();
        $this->assertContains($vendorA->id, $vendorIds);
        $this->assertContains($vendorB->id, $vendorIds);

        // Stock was deducted per vendor's own product only.
        $this->assertSame(48, $productA->fresh()->stock);
        $this->assertSame(49, $productB->fresh()->stock);

        // Cart is emptied after checkout.
        $this->assertSame(0, $cart->fresh()->items()->count());
    }

    public function test_checkout_reuses_pending_order_group_on_retry_instead_of_duplicating(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor('Solo Vendor');
        $product = $this->makeProduct($vendor, 75.00);

        $address = DeliveryAddress::create([
            'user_id' => $customer->id,
            'full_name' => 'Test Customer',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
        ]);

        $cart = EcommerceCart::create(['user_id' => $customer->id]);
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 1,
            'price_ghs' => 75.00,
        ]);

        $first = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id]);
        $first->assertCreated();

        // Re-add the same item (simulating the shopper going back) and retry checkout.
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 1,
            'price_ghs' => 75.00,
        ]);

        $second = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id]);
        $second->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        // Stock should only have been deducted once (50 - 1), not twice.
        $this->assertSame(49, $product->fresh()->stock);
    }

    public function test_payment_confirmation_credits_each_vendor_wallet_net_of_commission(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor('Commission Vendor', commissionRate: 10);
        $product = $this->makeProduct($vendor, 200.00);

        $address = DeliveryAddress::create([
            'user_id' => $customer->id,
            'full_name' => 'Test Customer',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
        ]);

        $cart = EcommerceCart::create(['user_id' => $customer->id]);
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 1,
            'price_ghs' => 200.00,
        ]);

        $checkout = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id]);
        $checkout->assertCreated();

        $groupId = $checkout->json('data.id');
        $totalGhs = (float) $checkout->json('data.total_ghs');

        $group = \App\Models\EcommerceOrderGroup::findOrFail($groupId);

        $paymentService = app(EcommercePaymentService::class);
        $markPaid = new \ReflectionMethod($paymentService, 'markPaid');
        $markPaid->setAccessible(true);
        $markPaid->invoke($paymentService, $group, 'paystack', 'TEST-REF-'.uniqid());

        $wallet = $vendor->wallet()->firstOrFail();
        $expectedVendorAmount = round($totalGhs * 0.90, 2);

        $this->assertEqualsWithDelta($expectedVendorAmount, (float) $wallet->pending_balance_ghs, 0.01);

        $this->assertDatabaseHas('vendor_transactions', [
            'vendor_id' => $vendor->id,
            'type' => VendorTransactionType::SaleCredit->value,
        ]);
        $this->assertDatabaseHas('vendor_transactions', [
            'vendor_id' => $vendor->id,
            'type' => VendorTransactionType::CommissionFee->value,
        ]);

        $group->refresh();
        $this->assertSame('paid', $group->payment_status->value);
        $this->assertSame('paid', $group->orders->first()->status->value);
    }

    public function test_vendor_application_approval_provisions_a_working_vendor_account(): void
    {
        $application = EcommerceVendorApplication::create([
            'business_name' => 'New Vendor Co',
            'contact_name' => 'Jane Vendor',
            'email' => 'jane-'.uniqid().'@example.com',
            'business_certificate_path' => 'x',
            'ghana_card_front_path' => 'x',
            'ghana_card_back_path' => 'x',
            'status' => 'pending',
        ]);

        $vendor = app(VendorProvisioningService::class)->approve($application, 'looks good', null);

        $this->assertSame('active', $vendor->status->value);
        $this->assertNotNull($vendor->wallet);

        $vendorUser = $vendor->user->fresh();
        $this->assertSame('vendor', $vendorUser->role);
        $this->assertSame($vendor->id, $vendorUser->vendor_id);
        $this->assertSame($application->email, $vendorUser->email);

        $this->assertSame('approved', $application->fresh()->status->value);

        // A second application with the same email must not silently overwrite the account.
        $duplicate = EcommerceVendorApplication::create([
            'business_name' => 'Duplicate Co',
            'contact_name' => 'Jane Vendor',
            'email' => $application->email,
            'business_certificate_path' => 'x',
            'ghana_card_front_path' => 'x',
            'ghana_card_back_path' => 'x',
            'status' => 'pending',
        ]);

        $this->expectException(\RuntimeException::class);
        app(VendorProvisioningService::class)->approve($duplicate, null, null);
    }

    public function test_coupon_discount_matches_between_cart_preview_and_checkout(): void
    {
        $customer = $this->makeCustomer();
        $vendor = $this->makeVendor('Coupon Vendor');
        $product = $this->makeProduct($vendor, 100.00);

        Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'created_by' => $vendor->user_id,
        ]);

        $address = DeliveryAddress::create([
            'user_id' => $customer->id,
            'full_name' => 'Test Customer',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Greater Accra',
            'city' => 'Accra',
        ]);

        $cart = EcommerceCart::create(['user_id' => $customer->id]);
        EcommerceCartItem::create([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'quantity' => 1,
            'price_ghs' => 100.00,
        ]);

        $preview = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/cart/coupon', ['code' => 'SAVE10']);
        $preview->assertOk();
        $this->assertSame('10.00', $preview->json('data.discount_ghs'));

        $checkout = $this->withHeaders($this->authHeaders($customer))
            ->postJson('/api/v1/marketplace/checkout', ['delivery_address_id' => $address->id]);
        $checkout->assertCreated();

        $this->assertEqualsWithDelta(10.00, (float) $checkout->json('data.discount_ghs'), 0.01);
    }

    public function test_guest_cart_merges_into_account_cart_on_login(): void
    {
        $vendor = $this->makeVendor('Guest Cart Vendor');
        $product = $this->makeProduct($vendor, 60.00);

        $guestSessionId = 'guest-session-'.uniqid();

        $addResponse = $this->withHeaders(['X-Guest-Session-Id' => $guestSessionId])
            ->postJson('/api/v1/marketplace/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        $addResponse->assertOk();

        $branch = $this->makeBranch();
        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Guest Turned Customer',
            'email' => 'guest-customer-'.uniqid().'@example.com',
        ]);
        $user = User::create([
            'name' => 'Guest Turned Customer',
            'email' => $client->email,
            'password' => bcrypt('password'),
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'role' => 'customer',
            'status' => 'active',
        ]);

        $loginResponse = $this->withHeaders(['X-Guest-Session-Id' => $guestSessionId])
            ->postJson('/api/v1/customer/auth/login', [
                'email' => $client->email,
                'password' => 'password',
            ]);
        $loginResponse->assertOk();

        $token = $loginResponse->json('data.token');

        $cartResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/marketplace/cart');

        $cartResponse->assertOk();
        $cartResponse->assertJsonCount(1, 'data.items');
        $this->assertSame($product->id, $cartResponse->json('data.items.0.product.id'));
    }
}
