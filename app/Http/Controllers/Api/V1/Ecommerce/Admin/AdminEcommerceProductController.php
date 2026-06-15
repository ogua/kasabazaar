<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Ecommerce\EcommerceProductImageResource;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\EcommerceProduct;
use App\Models\EcommerceProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminEcommerceProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $query = EcommerceProduct::where('branch_id', $branchId)
            ->with(['category', 'images'])
            ->withTrashed(false);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->filled('low_stock')) {
            $query->whereColumn('stock', '<=', 'low_stock_threshold');
        }

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%"));
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => new EcommerceProductResource($p));
    }

    public function store(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid|exists:ecommerce_categories,id',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'price_ghs' => 'required|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'discount_price_ghs' => 'nullable|numeric|min:0',
            'discount_price_usd' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product = EcommerceProduct::create(array_merge($data, ['branch_id' => $branchId]));

        return $this->success(new EcommerceProductResource($product->load(['category', 'images'])), 'Product created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $product = EcommerceProduct::where('branch_id', $branchId)
            ->with(['category', 'images'])
            ->findOrFail($id);

        return $this->success(new EcommerceProductResource($product));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid|exists:ecommerce_categories,id',
            'description' => 'nullable|string',
            'specifications' => 'nullable|array',
            'price_ghs' => 'sometimes|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'discount_price_ghs' => 'nullable|numeric|min:0',
            'discount_price_usd' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $product->update($data);

        return $this->success(new EcommerceProductResource($product->load(['category', 'images'])), 'Product updated.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);
        $product->delete();

        return $this->success(null, 'Product deleted.');
    }

    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);

        $request->validate([
            'image' => 'required|image|max:5120',
            'is_primary' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $path = $request->file('image')->store("ecommerce-product-images/{$product->id}", 'public');
        $image = EcommerceProductImage::create([
            'ecommerce_product_id' => $product->id,
            'path' => $path,
            'sort_order' => $request->input('sort_order', 0),
            'is_primary' => $request->boolean('is_primary', false),
        ]);

        return $this->success(new EcommerceProductImageResource($image), 'Image uploaded.', 201);
    }

    public function deleteImage(Request $request, string $id, string $imageId): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);

        $image = EcommerceProductImage::where('ecommerce_product_id', $product->id)->findOrFail($imageId);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $image->delete();

        return $this->success(null, 'Image deleted.');
    }

    public function toggleActive(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);

        return $this->success(new EcommerceProductResource($product), 'Product status toggled.');
    }

    public function toggleFeatured(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $product = EcommerceProduct::where('branch_id', $branchId)->findOrFail($id);
        $product->update(['is_featured' => ! $product->is_featured]);

        return $this->success(new EcommerceProductResource($product), 'Product featured status toggled.');
    }
}
