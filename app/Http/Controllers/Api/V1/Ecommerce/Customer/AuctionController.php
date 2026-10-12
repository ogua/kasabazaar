<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Exceptions\AuctionException;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\AuctionBidResource;
use App\Http\Resources\Ecommerce\AuctionResource;
use App\Http\Resources\Ecommerce\EcommerceOrderGroupResource;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Services\AuctionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends CustomerBaseController
{
    private const RELATIONS = ['vendor:id,business_name,slug,user_id', 'category:id,name', 'winningBid:id,user_id', 'orderGroup:id,payment_status'];

    public function __construct(protected AuctionService $auctionService) {}

    /**
     * Browse auctions. `state` is current (live + upcoming, the default),
     * live, upcoming or ended.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'state' => 'nullable|in:current,live,upcoming,ended',
            'search' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid',
            'per_page' => 'nullable|integer|min:1|max:48',
        ]);

        $state = $data['state'] ?? 'current';

        $query = Auction::query()
            ->visible()
            ->with(self::RELATIONS)
            ->when($data['search'] ?? null, fn (Builder $q, string $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($data['category_id'] ?? null, fn (Builder $q, string $categoryId) => $q->where('category_id', $categoryId));

        match ($state) {
            'live' => $query->live()->orderBy('ends_at'),
            'upcoming' => $query->upcoming()->orderBy('starts_at'),
            'ended' => $query->where(fn (Builder $q) => $q->where('status', 'ended')->orWhere('ends_at', '<=', now()))
                ->orderByDesc('ends_at'),
            default => $query->where('status', 'published')->where('ends_at', '>', now())
                // Live lots first (soonest to close), then upcoming by start.
                ->orderByRaw('CASE WHEN starts_at <= ? THEN 0 ELSE 1 END', [now()])
                ->orderBy('ends_at'),
        };

        $paginated = $query->paginate((int) ($data['per_page'] ?? 12));

        $this->preloadViewerBids($request, $paginated->getCollection()->pluck('id')->all());

        return $this->paginated($paginated, fn (Auction $auction) => new AuctionResource($auction));
    }

    public function show(string $slug): JsonResponse
    {
        $auction = Auction::visible()->with(self::RELATIONS)->where('slug', $slug)->firstOrFail();

        return $this->success(new AuctionResource($auction));
    }

    /**
     * The most recent bids, newest first, with masked bidder names.
     */
    public function bids(string $slug): JsonResponse
    {
        $auction = Auction::visible()->where('slug', $slug)->firstOrFail();

        $bids = $auction->bids()->with('user:id,name')->latest()->limit(20)->get();

        return $this->success(AuctionBidResource::collection($bids));
    }

    public function placeBid(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'amount_ghs' => 'required|numeric|min:0.01',
        ]);

        $auction = Auction::visible()->where('slug', $slug)->firstOrFail();

        try {
            $this->auctionService->placeBid($auction, $request->user(), (float) $data['amount_ghs'], $request->ip());
        } catch (AuctionException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new AuctionResource($auction->fresh(self::RELATIONS)),
            'Your bid has been placed.',
            201
        );
    }

    /**
     * Every auction the shopper has bid on, most recent activity first.
     */
    public function mine(Request $request): JsonResponse
    {
        $auctionIds = AuctionBid::where('user_id', $request->user()->id)->distinct()->pluck('auction_id');

        $paginated = Auction::visible()
            ->with(self::RELATIONS)
            ->whereIn('id', $auctionIds)
            ->orderByDesc('ends_at')
            ->paginate((int) $request->input('per_page', 20));

        $request->attributes->set('auction_ids_bid_on', $auctionIds->all());

        return $this->paginated($paginated, fn (Auction $auction) => new AuctionResource($auction));
    }

    /**
     * Create (or reuse) the order the winner pays through. The response is the
     * same order group shape as the cart checkout, so the storefront can hand
     * its id straight to POST payments/initiate.
     */
    public function checkout(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'delivery_address_id' => 'required|uuid',
            'notes' => 'nullable|string|max:500',
        ]);

        $auction = Auction::visible()->where('slug', $slug)->firstOrFail();

        try {
            $group = $this->auctionService->checkout($auction, $request->user(), $data['delivery_address_id'], $data['notes'] ?? '');
        } catch (AuctionException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new EcommerceOrderGroupResource($group), 'Order created. Complete payment to secure your item.', 201);
    }

    /**
     * A second-chance buyer turns the offer down; it moves on to the next
     * bidder straight away.
     */
    public function decline(Request $request, string $slug): JsonResponse
    {
        $auction = Auction::visible()->where('slug', $slug)->firstOrFail();

        try {
            $this->auctionService->declineOffer($auction, $request->user());
        } catch (AuctionException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, 'Offer declined. Thanks for letting us know.');
    }

    /**
     * @param  array<int, string>  $auctionIds
     */
    private function preloadViewerBids(Request $request, array $auctionIds): void
    {
        $viewer = auth('sanctum')->user();

        if (! $viewer) {
            return;
        }

        $request->attributes->set(
            'auction_ids_bid_on',
            AuctionBid::where('user_id', $viewer->id)->whereIn('auction_id', $auctionIds)->distinct()->pluck('auction_id')->all()
        );
    }
}
