<?php

use App\Livewire\Storefront\AuctionDetail;
use App\Models\User;
use App\Services\Kasabazaar\AuctionsApi;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/*
 * Auctions live in the kasabazaar backend; these tests fake its API and check
 * the storefront renders and calls it correctly.
 */

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

function fakeAuction(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'auc-1',
        'slug' => 'vintage-kente-abc123',
        'title' => 'Vintage Kente Cloth',
        'description' => 'Hand-woven, 1970s.',
        'condition' => 'Used — good',
        'images' => ['https://kasabazaar.test/storage/auction-images/kente.jpg'],
        'vendor' => ['business_name' => 'KASAROSE', 'slug' => 'kasarose'],
        'category' => null,
        'starting_price_ghs' => 100.0,
        'current_price_ghs' => 150.0,
        'bid_increment_ghs' => 10.0,
        'next_minimum_bid_ghs' => 160.0,
        'bids_count' => 2,
        'has_reserve' => true,
        'reserve_met' => false,
        'starts_at' => now()->subDay()->toIso8601String(),
        'ends_at' => now()->addDay()->toIso8601String(),
        'extension_minutes' => 2,
        'state' => 'live',
        'outcome' => null,
        'closed_at' => null,
    ], $overrides);
}

function fakeAuctionApi(array $auction, array $bids = []): void
{
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/auctions/*/bids' => Http::response(['success' => true, 'data' => $bids]),
        'kasabazaar.test/api/v1/marketplace/auctions/*' => Http::response(['success' => true, 'data' => $auction]),
        'kasabazaar.test/api/v1/marketplace/auctions*' => Http::response([
            'success' => true,
            'data' => [$auction],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => []]),
    ]);
}

it('lists auctions with their state and current bid', function () {
    fakeAuctionApi(fakeAuction());

    $this->get(route('storefront.auctions'))
        ->assertOk()
        ->assertSee('Vintage Kente Cloth')
        ->assertSee('GHS 150.00')
        ->assertSee('Current bid')
        ->assertSee('Live & upcoming');
});

it('shows an auction to a guest with a sign-in prompt and no reserve figure', function () {
    fakeAuctionApi(fakeAuction(), [
        ['id' => 'b2', 'bidder' => 'K***e', 'amount_ghs' => 150.0, 'placed_at' => now()->subMinutes(5)->toIso8601String(), 'is_mine' => false],
    ]);

    $this->get(route('storefront.auction', 'vintage-kente-abc123'))
        ->assertOk()
        ->assertSee('Vintage Kente Cloth')
        ->assertSee('Sign in to bid')
        ->assertSee('Not yet met')
        ->assertSee('K***e')
        ->assertDontSee('Confirm bid');
});

it('places a bid for a signed-in shopper and refreshes the auction', function () {
    $user = User::factory()->create(['kasabazaar_token' => 'token-123']);
    $this->actingAs($user);

    Http::fake([
        'kasabazaar.test/api/v1/marketplace/auctions/*/bids' => function (Request $request) {
            return $request->method() === 'POST'
                ? Http::response(['success' => true, 'data' => fakeAuction([
                    'current_price_ghs' => 200.0,
                    'next_minimum_bid_ghs' => 210.0,
                    'bids_count' => 3,
                    'viewer' => ['has_bid' => true, 'is_leading' => true, 'is_winner' => false],
                ])], 201)
                : Http::response(['success' => true, 'data' => []]);
        },
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => fakeAuction()]),
    ]);

    Livewire::test(AuctionDetail::class, ['slug' => 'vintage-kente-abc123'])
        ->set('bidAmount', '200')
        ->call('placeBid')
        ->assertHasNoErrors()
        ->assertSet('auction.current_price_ghs', 200.0)
        ->assertSet('bidAmount', '210.00')
        ->assertDispatched('toast');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/marketplace/auctions/vintage-kente-abc123/bids')
        && (float) $request['amount_ghs'] === 200.0
        && $request->hasHeader('Authorization', 'Bearer token-123'));
});

it('rejects a bid below the minimum before calling the API', function () {
    $user = User::factory()->create(['kasabazaar_token' => 'token-123']);
    $this->actingAs($user);
    fakeAuctionApi(fakeAuction());

    Livewire::test(AuctionDetail::class, ['slug' => 'vintage-kente-abc123'])
        ->set('bidAmount', '155')
        ->call('placeBid')
        ->assertHasErrors(['bidAmount' => 'min']);

    Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
});

it('shows the API reason when a bid is refused', function () {
    $user = User::factory()->create(['kasabazaar_token' => 'token-123']);
    $this->actingAs($user);

    Http::fake([
        'kasabazaar.test/api/v1/marketplace/auctions/*/bids' => fn (Request $request) => $request->method() === 'POST'
            ? Http::response(['success' => false, 'message' => 'This auction has ended and is no longer accepting bids.'], 422)
            : Http::response(['success' => true, 'data' => []]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => fakeAuction()]),
    ]);

    Livewire::test(AuctionDetail::class, ['slug' => 'vintage-kente-abc123'])
        ->set('bidAmount', '160')
        ->call('placeBid')
        ->assertSet('bidError', 'This auction has ended and is no longer accepting bids.');
});

it('sends the winner checkout to the auction endpoint', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => true, 'data' => ['id' => 'grp-9']], 201)]);

    $group = app(AuctionsApi::class)->checkout('vintage-kente-abc123', 'addr-1', 'Call on arrival');

    expect($group['id'])->toBe('grp-9');
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/marketplace/auctions/vintage-kente-abc123/checkout')
        && $request['delivery_address_id'] === 'addr-1'
        && $request['notes'] === 'Call on arrival');
});

it('only lets the winner reach the payment page', function () {
    $user = User::factory()->create(['kasabazaar_token' => 'token-123']);
    fakeAuctionApi(fakeAuction(['state' => 'ended', 'outcome' => 'sold', 'viewer' => ['is_winner' => false, 'has_bid' => true, 'is_leading' => false]]));

    $this->actingAs($user)
        ->get(route('storefront.auction.pay', 'vintage-kente-abc123'))
        ->assertOk()
        ->assertSee('Nothing to pay here');

    auth()->logout();
    $this->get(route('storefront.auction.pay', 'vintage-kente-abc123'))
        ->assertRedirect(route('storefront.login'));
});

it('renders homepage banners from the API with their call to action', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/banners*' => Http::response(['success' => true, 'data' => [[
            'id' => 'ban-1',
            'title' => 'Bid on one-of-a-kind finds.',
            'subtitle' => 'Place your bid and watch the clock.',
            'eyebrow' => 'Live auctions',
            'cta_label' => 'Start bidding',
            'link_url' => '/auctions',
            'image_url' => 'https://kasabazaar.test/storage/banners/hero-auctions.jpg',
            'position' => 'homepage_hero',
            'sort_order' => 1,
        ]]]),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => [], 'meta' => []]),
    ]);

    $this->get(route('storefront.home'))
        ->assertOk()
        ->assertSee('Bid on one-of-a-kind finds.')
        ->assertSee('Start bidding')
        ->assertSee(url('/auctions'), false)
        ->assertSee('https://kasabazaar.test/storage/banners/hero-auctions.jpg', false);
});

it('falls back to the brand hero when the banner API is unavailable', function () {
    Http::fake([
        'kasabazaar.test/api/v1/marketplace/banners*' => Http::response(['success' => false, 'message' => 'Down'], 500),
        'kasabazaar.test/*' => Http::response(['success' => true, 'data' => [], 'meta' => []]),
    ]);

    $this->get(route('storefront.home'))
        ->assertOk()
        ->assertSee('Shop local vendors, delivered with confidence.');
});

it('publishes auction rules in the terms of use', function () {
    Http::fake(['kasabazaar.test/*' => Http::response(['success' => true, 'data' => []])]);

    $this->get(route('storefront.terms'))
        ->assertOk()
        ->assertSee('id="auctions"', false)
        ->assertSee('Bids are binding.')
        ->assertSee(config('group.auctions.payment_window_hours').' hours of the auction closing');
});
