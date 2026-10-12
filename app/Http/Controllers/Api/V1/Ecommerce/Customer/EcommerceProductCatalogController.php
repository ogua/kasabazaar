<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceCategoryResource;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\Auction;
use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use Illuminate\Database\Eloquent\Builder;
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
            $this->applySearch($query, (string) $request->search);
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

    /**
     * Products to show beside a product page: similar items (same category,
     * nearest in price first) and more from the same vendor.
     */
    public function related(string $id): JsonResponse
    {
        $product = EcommerceProduct::where('is_active', true)->findOrFail($id);

        $base = fn () => EcommerceProduct::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('vendor', fn ($q) => $q->active())
            ->with(['category:id,name', 'images', 'vendor:id,business_name,slug']);

        $price = (float) ($product->discount_price_ghs ?? $product->price_ghs);

        $similar = $product->category_id
            ? $base()->where('category_id', $product->category_id)
                ->orderByRaw('ABS(COALESCE(discount_price_ghs, price_ghs) - ?)', [$price])
                ->limit(10)
                ->get()
            : collect();

        $fromVendor = $base()->where('vendor_id', $product->vendor_id)
            ->whereNotIn('id', $similar->pluck('id'))
            ->latest()
            ->limit(10)
            ->get();

        return $this->success([
            'similar' => EcommerceProductResource::collection($similar),
            'from_vendor' => EcommerceProductResource::collection($fromVendor),
        ]);
    }

    /**
     * Type-ahead suggestions for the storefront search box: a few matching
     * products, categories and live auctions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'required|string|min:2|max:100']);
        $term = trim($data['q']);

        $products = $this->applySearch(
            EcommerceProduct::where('is_active', true)
                ->whereHas('vendor', fn ($q) => $q->active())
                ->with(['images', 'vendor:id,business_name,slug']),
            $term
        )
            // Name matches first, then everything else.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', ["{$term}%", "%{$term}%"])
            ->limit(6)
            ->get();

        $categories = EcommerceCategory::where('is_active', true)
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(3)
            ->get();

        $auctions = Auction::live()
            ->where('title', 'like', "%{$term}%")
            ->orderBy('ends_at')
            ->limit(3)
            ->get();

        return $this->success([
            'products' => $products->map(fn (EcommerceProduct $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price_ghs' => (float) ($p->discount_price_ghs ?? $p->price_ghs),
                'image_url' => $p->images->first() ? asset('storage/'.$p->images->first()->path) : null,
                'vendor' => $p->vendor?->business_name,
            ])->values(),
            'categories' => EcommerceCategoryResource::collection($categories),
            'auctions' => $auctions->map(fn (Auction $a) => [
                'slug' => $a->slug,
                'title' => $a->title,
                'current_price_ghs' => (float) ($a->current_price_ghs ?? $a->starting_price_ghs),
                'image_url' => $a->imageUrls()[0] ?? null,
            ])->values(),
        ]);
    }

    /**
     * Search across what shoppers actually type: product name, SKU and
     * description, plus the category name, the vendor's name and the product's
     * specifications (brand, model...). Every word must match somewhere, so
     * "samsung tv" finds a Samsung television in the TV category.
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        $words = collect(preg_split('/\s+/', trim($search)))->filter()->take(5);

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('description', 'like', $like)
                // JSON columns compare case-sensitively in MySQL, so lower both sides.
                ->orWhereRaw('LOWER(CAST(specifications AS CHAR)) LIKE ?', [mb_strtolower($like)])
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('vendor', fn (Builder $v) => $v->where('business_name', 'like', $like)));
        }

        return $query;
    }
}
