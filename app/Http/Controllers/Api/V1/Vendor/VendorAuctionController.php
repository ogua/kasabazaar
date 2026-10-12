<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\AuctionStatus;
use App\Exceptions\AuctionException;
use App\Http\Resources\Ecommerce\AuctionBidResource;
use App\Http\Resources\Ecommerce\VendorAuctionResource;
use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Vendor self-service auctions. Vendors create and edit drafts, upload
 * photos and submit lots for review; only staff can publish (Filament
 * E-Commerce → Auctions). Once a lot is published it is read-only here.
 */
class VendorAuctionController extends VendorBaseController
{
    public function __construct(protected AuctionService $auctionService) {}

    public function index(Request $request): JsonResponse
    {
        $paginated = Auction::where('vendor_id', $this->vendorId())
            ->with('orderGroup:id,payment_status')
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn (Auction $auction) => new VendorAuctionResource($auction));
    }

    public function store(Request $request): JsonResponse
    {
        $auction = Auction::create($this->validated($request) + [
            'vendor_id' => $this->vendorId(),
            'status' => AuctionStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        return $this->success(new VendorAuctionResource($auction), 'Auction draft created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        return $this->success(new VendorAuctionResource($this->findOwn($id)->load('orderGroup:id,payment_status')));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $auction = $this->findEditable($id);

        $data = $this->validated($request, partial: true);

        // Editing a lot under review sends it back to draft so staff never
        // approve something different from what they reviewed.
        if ($auction->status === AuctionStatus::PendingReview) {
            $data['status'] = AuctionStatus::Draft;
            $data['submitted_at'] = null;
        }

        if (isset($data['ends_at'])) {
            $data['original_ends_at'] = $data['ends_at'];
        }

        $auction->update($data);

        return $this->success(new VendorAuctionResource($auction->fresh()), 'Auction updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $auction = $this->findEditable($id);

        foreach ($auction->image_paths ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $auction->delete();

        return $this->success(null, 'Auction deleted.');
    }

    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $auction = $this->findEditable($id);

        $request->validate(['image' => 'required|image|max:4096']);

        $paths = $auction->image_paths ?? [];
        abort_if(count($paths) >= 8, 422, 'An auction can have at most 8 photos.');

        $paths[] = $request->file('image')->store('auction-images', 'public');
        $auction->update(['image_paths' => $paths]);

        return $this->success(new VendorAuctionResource($auction), 'Photo added.', 201);
    }

    public function deleteImage(string $id, int $index): JsonResponse
    {
        $auction = $this->findEditable($id);
        $paths = $auction->image_paths ?? [];

        abort_unless(isset($paths[$index]), 404, 'Photo not found.');

        Storage::disk('public')->delete($paths[$index]);
        unset($paths[$index]);
        $auction->update(['image_paths' => array_values($paths)]);

        return $this->success(new VendorAuctionResource($auction), 'Photo removed.');
    }

    public function submit(string $id): JsonResponse
    {
        try {
            $auction = $this->auctionService->submitForReview($this->findOwn($id));
        } catch (AuctionException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new VendorAuctionResource($auction), 'Submitted for review. We will publish it once approved.');
    }

    public function bids(string $id): JsonResponse
    {
        $auction = $this->findOwn($id);

        return $this->success(AuctionBidResource::collection($auction->bids()->with('user:id,name')->latest()->limit(50)->get()));
    }

    private function findOwn(string $id): Auction
    {
        return Auction::where('vendor_id', $this->vendorId())->findOrFail($id);
    }

    private function findEditable(string $id): Auction
    {
        $auction = $this->findOwn($id);

        abort_unless(
            in_array($auction->status, [AuctionStatus::Draft, AuctionStatus::PendingReview], true),
            422,
            'A published auction can no longer be changed. Contact us if something is wrong.'
        );

        return $auction;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'condition' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'uuid', 'exists:ecommerce_categories,id'],
            'starting_price_ghs' => [$required, 'numeric', 'min:1'],
            'reserve_price_ghs' => ['nullable', 'numeric', 'min:0', 'gte:starting_price_ghs'],
            'bid_increment_ghs' => [$required, 'numeric', 'min:1'],
            'starts_at' => [$required, 'date'],
            'ends_at' => [$required, 'date', 'after:starts_at', 'after:now'],
            'extension_minutes' => ['nullable', 'integer', 'min:0', 'max:30'],
        ]);
    }
}
