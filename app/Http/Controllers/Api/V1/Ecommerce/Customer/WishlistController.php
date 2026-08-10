<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\EcommerceProduct;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $products = EcommerceProduct::whereIn('id', Wishlist::where('user_id', auth()->id())->pluck('ecommerce_product_id'))
            ->with(['category:id,name', 'images'])
            ->get();

        return $this->success(EcommerceProductResource::collection($products));
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        EcommerceProduct::findOrFail($productId);

        Wishlist::firstOrCreate([
            'user_id' => auth()->id(),
            'ecommerce_product_id' => $productId,
        ]);

        return $this->success(null, 'Added to wishlist.', 201);
    }

    public function destroy(Request $request, string $productId): JsonResponse
    {
        Wishlist::where('user_id', auth()->id())->where('ecommerce_product_id', $productId)->delete();

        return $this->success(null, 'Removed from wishlist.');
    }
}
