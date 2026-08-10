<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorReviewController extends VendorBaseController
{
    public function index(Request $request): JsonResponse
    {
        $paginated = ProductReview::whereHas('product', fn ($q) => $q->where('vendor_id', $this->vendorId()))
            ->with(['product:id,name', 'user:id,name'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated);
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $review = ProductReview::whereHas('product', fn ($q) => $q->where('vendor_id', $this->vendorId()))
            ->findOrFail($id);

        $data = $request->validate(['reply' => 'required|string|max:1000']);

        $review->update([
            'vendor_reply' => $data['reply'],
            'vendor_replied_at' => now(),
        ]);

        return $this->success($review, 'Reply posted.');
    }
}
