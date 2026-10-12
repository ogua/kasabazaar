<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Ecommerce\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends BaseApiController
{
    /**
     * Live banners for one storefront slot, in display order.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'position' => ['nullable', Rule::in(array_keys(Banner::POSITIONS))],
        ]);

        $banners = Banner::live()
            ->where('position', $data['position'] ?? 'homepage_hero')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return $this->success(BannerResource::collection($banners));
    }
}
