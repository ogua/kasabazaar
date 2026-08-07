<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Models\EcommerceCart;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends CustomerBaseController
{
    public function __construct(protected CouponService $couponService) {}

    /**
     * Preview-only validation (e.g. a "have a coupon?" field before adding it
     * to the cart) — does not persist anything. Actual application happens via
     * EcommerceCartController::applyCoupon, which uses the same CouponService.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string']);

        $cart = EcommerceCart::where('user_id', auth()->id())->with('items')->first();
        $subtotal = (float) ($cart?->items->sum(fn ($item) => $item->price_ghs * $item->quantity) ?? 0);

        try {
            $result = $this->couponService->validate($data['code'], auth()->user(), $subtotal);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['discount_ghs' => $result['discount_ghs']]);
    }
}
