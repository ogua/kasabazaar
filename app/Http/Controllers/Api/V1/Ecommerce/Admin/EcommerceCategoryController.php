<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Ecommerce\EcommerceCategoryResource;
use App\Models\EcommerceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCategoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $categories = EcommerceCategory::where('branch_id', $branchId)
            ->withCount('products')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return $this->success(EcommerceCategoryResource::collection($categories));
    }

    public function store(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:ecommerce_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category = EcommerceCategory::create(array_merge($data, ['branch_id' => $branchId]));

        return $this->success(new EcommerceCategoryResource($category), 'Category created.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $category = EcommerceCategory::where('branch_id', $branchId)
            ->withCount('products')
            ->with('children')
            ->findOrFail($id);

        return $this->success(new EcommerceCategoryResource($category));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $category = EcommerceCategory::where('branch_id', $branchId)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:ecommerce_categories,id',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'image' => 'nullable|string',
        ]);

        $category->update($data);

        return $this->success(new EcommerceCategoryResource($category), 'Category updated.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $category = EcommerceCategory::where('branch_id', $branchId)->findOrFail($id);
        $category->delete();

        return $this->success(null, 'Category deleted.');
    }

    public function toggleActive(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);

        $category = EcommerceCategory::where('branch_id', $branchId)->findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        return $this->success(new EcommerceCategoryResource($category), 'Category status toggled.');
    }

    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $branchId = $this->resolveBranch($request);
        $category = EcommerceCategory::where('branch_id', $branchId)->findOrFail($id);

        $request->validate(['image' => 'required|image|max:5120']);

        $path = $request->file('image')->store("ecommerce-category-images/{$category->id}", 'public');
        $category->update(['image' => $path]);

        return $this->success(['image_url' => asset('storage/'.$path)], 'Image uploaded.');
    }
}
