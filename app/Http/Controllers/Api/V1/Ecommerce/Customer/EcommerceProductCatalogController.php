<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceProductCatalogController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = EcommerceProduct::where('is_active', true)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->with(['category:id,name', 'images', 'vendor:id,business_name,slug']);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($request->filled('price_min')) {
            $query->where('price_ghs', '>=', (float) $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price_ghs', '<=', (float) $request->price_max);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        $sort = $request->input('sort', 'newest');

        match ($sort) {
            'price_asc' => $query->orderBy('price_ghs'),
            'price_desc' => $query->orderByDesc('price_ghs'),
            'popular' => $query->withCount('orderItems')->orderByDesc('order_items_count'),
            default => $query->latest(),
        };

        $paginated = $query->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => new EcommerceProductResource($p));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $product = EcommerceProduct::where('is_active', true)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->with(['category:id,name,slug', 'images', 'vendor:id,business_name,slug'])
            ->findOrFail($id);

        return $this->success(new EcommerceProductResource($product));
    }
}
