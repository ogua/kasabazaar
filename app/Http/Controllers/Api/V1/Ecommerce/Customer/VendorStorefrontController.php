<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceProductResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorStorefrontController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::active()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('business_name')
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($vendors, fn ($v) => $this->formatVendor($v));
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $vendor = Vendor::active()->where('slug', $slug)->firstOrFail();

        return $this->success($this->formatVendor($vendor));
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        $vendor = Vendor::active()->where('slug', $slug)->firstOrFail();

        $query = $vendor->products()->where('is_active', true)->with(['category:id,name', 'images']);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($p) => new EcommerceProductResource($p));
    }

    private function formatVendor(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'business_name' => $vendor->business_name,
            'slug' => $vendor->slug,
            'logo_url' => $vendor->logo_path ? asset('storage/'.$vendor->logo_path) : null,
            'banner_url' => $vendor->banner_path ? asset('storage/'.$vendor->banner_path) : null,
            'description' => $vendor->description,
            'products_count' => $vendor->products_count ?? $vendor->products()->where('is_active', true)->count(),
        ];
    }
}
