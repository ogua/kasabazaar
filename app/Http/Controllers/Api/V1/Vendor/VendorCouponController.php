<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorCouponController extends VendorBaseController
{
    public function index(Request $request): JsonResponse
    {
        $paginated = Coupon::where('vendor_id', $this->vendorId())
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount_ghs' => 'nullable|numeric|min:0',
            'max_discount_ghs' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $coupon = Coupon::create(array_merge($data, [
            'vendor_id' => $this->vendorId(),
            'created_by' => auth()->id(),
            'is_active' => true,
        ]));

        return $this->success($coupon, 'Coupon created.', 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::where('vendor_id', $this->vendorId())->findOrFail($id);

        $data = $request->validate([
            'value' => 'sometimes|numeric|min:0',
            'min_order_amount_ghs' => 'nullable|numeric|min:0',
            'max_discount_ghs' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $coupon->update($data);

        return $this->success($coupon->fresh(), 'Coupon updated.');
    }
}
