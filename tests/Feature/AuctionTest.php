<?php

namespace Tests\Feature;

use App\Enums\AuctionOutcome;
use App\Enums\AuctionStatus;
use App\Enums\EcommerceOrderPaymentStatus;
use App\Exceptions\AuctionException;
use App\Models\Auction;
use App\Models\Banner;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DeliveryAddress;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Notifications\Ecommerce\AuctionOutbid;
use App\Notifications\Ecommerce\AuctionWon;
use App\Services\AuctionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuctionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
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
            'business_name' => 'Auction House '.uniqid(),
            'commission_rate' => 10,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $owner->update(['vendor_id' => $vendor->id]);
        VendorWallet::create(['vendor_id' => $vendor->id]);

        return $vendor;
    }

    private function makeCustomer(string $name = 'Kwame Mensah'): User
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

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => $name,
            'email' => 'customer-'.uniqid().'@example.com',
        ]);

        return User::create([
            'name' => $name,
            'email' => $client->email,
            'password' => bcrypt('password'),
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    private function makeAuction(array $overrides = []): Auction
    {
        return Auction::create(array_merge([
            'vendor_id' => $this->makeVendor()->id,
            'title' => 'Vintage Kente Cloth '.uniqid(),
            'starting_price_ghs' => 100,
            'bid_increment_ghs' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'extension_minutes' => 2,
            'status' => AuctionStatus::Published,
        ], $overrides));
    }

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    private function service(): AuctionService
    {
        return app(AuctionService::class);
    }

    public function test_the_first_bid_must_meet_the_starting_price_and_later_bids_the_increment(): void
    {
        $auction = $this->makeAuction();
        $alice = $this->makeCustomer('Alice');
        $bob = $this->makeCustomer('Bob');

        try {
            $this->service()->placeBid($auction, $alice, 99);
            $this->fail('A bid under the starting price was accepted.');
        } catch (AuctionException $e) {
            $this->assertStringContainsString('100.00', $e->getMessage());
        }

        $this->service()->placeBid($auction, $alice, 100);

        $this->expectException(AuctionException::class);
        $this->service()->placeBid($auction, $bob, 105);
    }

    public function test_a_valid_bid_updates_the_auction_and_notifies_the_outbid_leader(): void
    {
        $auction = $this->makeAuction();
        $alice = $this->makeCustomer('Alice');
        $bob = $this->makeCustomer('Bob');

        $this->service()->placeBid($auction, $alice, 100);
        $bid = $this->service()->placeBid($auction, $bob, 150);

        $auction->refresh();
        $this->assertSame(150.0, (float) $auction->current_price_ghs);
        $this->assertSame(2, $auction->bids_count);
        $this->assertSame($bid->id, $auction->winning_bid_id);
        $this->assertSame(160.0, $auction->nextMinimumBid());

        Notification::assertSentTo($alice, AuctionOutbid::class);
        Notification::assertNotSentTo($bob, AuctionOutbid::class);
    }

    public function test_the_leading_bidder_and_the_seller_cannot_bid(): void
    {
        $auction = $this->makeAuction();
        $alice = $this->makeCustomer('Alice');

        $this->service()->placeBid($auction, $alice, 100);

        try {
            $this->service()->placeBid($auction, $alice, 200);
            $this->fail('The leading bidder was allowed to bid against themselves.');
        } catch (AuctionException $e) {
            $this->assertSame('You are already the highest bidder.', $e->getMessage());
        }

        $this->expectExceptionMessage('You cannot bid on your own auction.');
        $this->service()->placeBid($auction, $auction->vendor->user, 500);
    }

    public function test_bids_are_refused_before_the_auction_opens_and_after_it_ends(): void
    {
        $customer = $this->makeCustomer();

        $upcoming = $this->makeAuction(['starts_at' => now()->addHour(), 'ends_at' => now()->addDay()]);
        $ended = $this->makeAuction(['starts_at' => now()->subDays(2), 'ends_at' => now()->subMinute()]);
        $draft = $this->makeAuction(['status' => AuctionStatus::Draft]);

        foreach ([$upcoming, $ended, $draft] as $auction) {
            try {
                $this->service()->placeBid($auction, $customer, 1000);
                $this->fail("A bid was accepted on a {$auction->state()} auction.");
            } catch (AuctionException) {
                $this->assertSame(0, $auction->fresh()->bids_count);
            }
        }
    }

    public function test_a_bid_in_the_closing_window_extends_the_auction(): void
    {
        $auction = $this->makeAuction(['ends_at' => now()->addSeconds(30)]);

        $this->service()->placeBid($auction, $this->makeCustomer(), 100);

        $this->assertTrue($auction->fresh()->ends_at->greaterThan(now()->addSeconds(110)));
    }

    public function test_closing_a_sold_auction_records_the_winner_and_opens_the_payment_window(): void
    {
        $auction = $this->makeAuction(['reserve_price_ghs' => 120]);
        $winner = $this->makeCustomer();
        $this->service()->placeBid($auction, $winner, 130);
        $auction->update(['ends_at' => now()->subMinute()]);

        $closedCount = $this->service()->closeDue();

        $auction->refresh();
        $this->assertGreaterThanOrEqual(1, $closedCount);
        $this->assertSame(AuctionStatus::Ended, $auction->status);
        $this->assertSame(AuctionOutcome::Sold, $auction->outcome);
        $this->assertSame($winner->id, $auction->winner_user_id);
        $this->assertTrue($auction->payment_due_at->isFuture());
        Notification::assertSentTo($winner, AuctionWon::class);

        // Settling again is a no-op.
        $this->service()->close($auction);
        Notification::assertSentToTimes($winner, AuctionWon::class, 1);
    }

    public function test_an_auction_below_its_reserve_or_without_bids_does_not_sell(): void
    {
        $belowReserve = $this->makeAuction(['reserve_price_ghs' => 500]);
        $bidder = $this->makeCustomer();
        $this->service()->placeBid($belowReserve, $bidder, 100);
        $belowReserve->update(['ends_at' => now()->subMinute()]);

        $noBids = $this->makeAuction(['ends_at' => now()->subMinute()]);

        $this->service()->close($belowReserve);
        $this->service()->close($noBids);

        $this->assertSame(AuctionOutcome::ReserveNotMet, $belowReserve->fresh()->outcome);
        $this->assertNull($belowReserve->fresh()->winner_user_id);
        $this->assertSame(AuctionOutcome::NoBids, $noBids->fresh()->outcome);
        Notification::assertNotSentTo($bidder, AuctionWon::class);
    }

    public function test_the_winner_pays_through_a_single_item_order_at_the_winning_price(): void
    {
        $auction = $this->makeAuction();
        $winner = $this->makeCustomer();
        $this->service()->placeBid($auction, $winner, 250);
        $auction->update(['ends_at' => now()->subMinute()]);
        $this->service()->close($auction);

        $address = DeliveryAddress::create([
            'user_id' => $winner->id,
            'full_name' => 'Kwame Mensah',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Ashanti',
            'city' => 'Kumasi',
            'is_default' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders($winner))
            ->postJson("/api/v1/marketplace/auctions/{$auction->slug}/checkout", ['delivery_address_id' => $address->id]);

        $response->assertCreated();
        $groupId = $response->json('data.id');

        $auction->refresh();
        $this->assertSame($groupId, $auction->order_group_id);
        $this->assertSame(EcommerceOrderPaymentStatus::Pending, $auction->orderGroup->payment_status);

        $order = $auction->orderGroup->orders()->with('items')->sole();
        $this->assertSame($auction->vendor_id, $order->vendor_id);
        $item = $order->items->sole();
        $this->assertNull($item->ecommerce_product_id);
        $this->assertSame(250.0, (float) $item->unit_price_ghs);
        $this->assertSame(1, (int) $item->quantity);

        // Retrying reuses the pending order instead of creating a second one.
        $this->withHeaders($this->authHeaders($winner))
            ->postJson("/api/v1/marketplace/auctions/{$auction->slug}/checkout", ['delivery_address_id' => $address->id])
            ->assertCreated()
            ->assertJsonPath('data.id', $groupId);
    }

    public function test_only_the_winner_can_check_out(): void
    {
        $auction = $this->makeAuction();
        $this->service()->placeBid($auction, $this->makeCustomer(), 250);
        $auction->update(['ends_at' => now()->subMinute()]);
        $this->service()->close($auction);

        $stranger = $this->makeCustomer('Stranger');
        $address = DeliveryAddress::create([
            'user_id' => $stranger->id,
            'full_name' => 'Stranger',
            'phone' => '0200000000',
            'country' => 'Ghana',
            'region' => 'Ashanti',
            'city' => 'Kumasi',
        ]);

        $this->withHeaders($this->authHeaders($stranger))
            ->postJson("/api/v1/marketplace/auctions/{$auction->slug}/checkout", ['delivery_address_id' => $address->id])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only the winning bidder can pay for this auction.');
    }

    public function test_the_public_api_never_exposes_the_reserve_price_and_masks_bidders(): void
    {
        $auction = $this->makeAuction(['reserve_price_ghs' => 999]);
        $this->service()->placeBid($auction, $this->makeCustomer('Kwame Mensah'), 100);

        $show = $this->getJson("/api/v1/marketplace/auctions/{$auction->slug}")->assertOk();
        $show->assertJsonPath('data.state', 'live')
            ->assertJsonPath('data.has_reserve', true)
            ->assertJsonPath('data.reserve_met', false)
            ->assertJsonMissingPath('data.reserve_price_ghs')
            ->assertJsonMissingPath('data.viewer');
        $this->assertStringNotContainsString('999', $show->getContent());

        $this->getJson("/api/v1/marketplace/auctions/{$auction->slug}/bids")
            ->assertOk()
            ->assertJsonPath('data.0.bidder', 'K***e')
            ->assertJsonPath('data.0.amount_ghs', 100);

        $this->getJson('/api/v1/marketplace/auctions?state=live')
            ->assertOk()
            ->assertJsonFragment(['slug' => $auction->slug]);
    }

    public function test_drafts_are_hidden_and_bidding_requires_login(): void
    {
        $draft = $this->makeAuction(['status' => AuctionStatus::Draft]);
        $live = $this->makeAuction();

        $this->getJson("/api/v1/marketplace/auctions/{$draft->slug}")->assertNotFound();

        $this->postJson("/api/v1/marketplace/auctions/{$live->slug}/bids", ['amount_ghs' => 100])
            ->assertUnauthorized();

        $customer = $this->makeCustomer();
        $this->withHeaders($this->authHeaders($customer))
            ->postJson("/api/v1/marketplace/auctions/{$live->slug}/bids", ['amount_ghs' => 100])
            ->assertCreated()
            ->assertJsonPath('data.current_price_ghs', 100)
            ->assertJsonPath('data.viewer.is_leading', true);
    }

    public function test_the_banner_endpoint_returns_live_banners_for_a_position_in_order(): void
    {
        Banner::query()->delete();

        Banner::create(['title' => 'Second', 'image_path' => 'banners/b.jpg', 'position' => 'homepage_hero', 'sort_order' => 2]);
        Banner::create(['title' => 'First', 'image_path' => 'banners/a.jpg', 'position' => 'homepage_hero', 'sort_order' => 1]);
        Banner::create(['title' => 'Hidden', 'image_path' => 'banners/c.jpg', 'position' => 'homepage_hero', 'is_active' => false]);
        Banner::create(['title' => 'Expired', 'image_path' => 'banners/d.jpg', 'position' => 'homepage_hero', 'ends_at' => now()->subDay()]);
        Banner::create(['title' => 'Promo', 'image_path' => 'banners/e.jpg', 'position' => 'homepage_promo_1']);

        $response = $this->getJson('/api/v1/marketplace/banners?position=homepage_hero')->assertOk();

        $this->assertSame(['First', 'Second'], array_column($response->json('data'), 'title'));
        $this->assertStringEndsWith('/storage/banners/a.jpg', $response->json('data.0.image_url'));
    }

    public function test_masked_names_reveal_only_the_first_and_last_letter(): void
    {
        $auction = $this->makeAuction();
        $bid = $this->service()->placeBid($auction, $this->makeCustomer('Ama'), 100);

        $this->assertSame('A***a', $bid->fresh('user')->maskedBidderName());
        $this->assertSame(Str::length('A***a'), 5);
    }
}
