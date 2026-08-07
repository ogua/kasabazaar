<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Resources\Ecommerce\EcommerceCategoryResource;
use App\Models\EcommerceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorCategoryController extends VendorBaseController
{
    /**
     * Categories are global marketplace taxonomy (owned by staff) — vendors can
     * only browse them to assign products, not create/edit/delete.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = EcommerceCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success(EcommerceCategoryResource::collection($categories));
    }
}
