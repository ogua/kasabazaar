<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceCategoryResource;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EcommerceHomeController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $categories = EcommerceCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount('products')
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        $featured = EcommerceProduct::where('is_active', true)
            ->where('is_featured', true)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->with(['category:id,name', 'images', 'vendor:id,business_name,slug'])
            ->limit(10)
            ->get();

        $newArrivals = EcommerceProduct::where('is_active', true)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->with(['category:id,name', 'images', 'vendor:id,business_name,slug'])
            ->latest()
            ->limit(10)
            ->get();

        $bestSellerIds = DB::table('ecommerce_order_items')
            ->select('ecommerce_product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereNotNull('ecommerce_product_id')
            ->groupBy('ecommerce_product_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->pluck('ecommerce_product_id');

        $bestSellers = EcommerceProduct::where('is_active', true)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->whereIn('id', $bestSellerIds)
            ->with(['category:id,name', 'images', 'vendor:id,business_name,slug'])
            ->get()
            ->sortBy(fn ($p) => $bestSellerIds->search($p->id))
            ->values();

        return $this->success([
            'categories' => EcommerceCategoryResource::collection($categories),
            'featured' => EcommerceProductResource::collection($featured),
            'new_arrivals' => EcommerceProductResource::collection($newArrivals),
            'best_sellers' => EcommerceProductResource::collection($bestSellers),
        ]);
    }
}
