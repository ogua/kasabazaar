<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceCategoryResource;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\EcommerceCategory;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCategoryBrowseController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $branchId = $this->customerBranchId();

        $categories = EcommerceCategory::where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount('products')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return $this->success(EcommerceCategoryResource::collection($categories));
    }

    public function products(Request $request, string $id): JsonResponse
    {
        $branchId = $this->customerBranchId();

        $category = EcommerceCategory::where('branch_id', $branchId)
            ->where('is_active', true)
            ->findOrFail($id);

        $query = EcommerceProduct::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('category_id', $category->id)
            ->with(['category:id,name', 'images']);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => new EcommerceProductResource($p));
    }
}
