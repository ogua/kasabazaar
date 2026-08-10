<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\EcommerceOrderItem;
use App\Models\EcommerceProduct;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends CustomerBaseController
{
    public function index(Request $request, string $productId): JsonResponse
    {
        $reviews = ProductReview::where('ecommerce_product_id', $productId)
            ->where('is_approved', true)
            ->with('user:id,name')
            ->latest()
            ->paginate((int) $request->input('per_page', 10));

        return $this->paginated($reviews);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $product = EcommerceProduct::findOrFail($productId);

        abort_if(ProductReview::where('user_id', auth()->id())->where('ecommerce_product_id', $product->id)->exists(), 422, 'You have already reviewed this product.');

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:2000',
        ]);

        $verifiedItem = EcommerceOrderItem::where('ecommerce_product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', auth()->id())->where('status', 'delivered'))
            ->first();

        $review = ProductReview::create(array_merge($data, [
            'ecommerce_product_id' => $product->id,
            'user_id' => auth()->id(),
            'ecommerce_order_item_id' => $verifiedItem?->id,
        ]));

        return $this->success($review, 'Review submitted.', 201);
    }
}
