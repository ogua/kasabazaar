<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;

class CouponService
{
    /**
     * @return array{coupon: Coupon, discount_ghs: float}
     *
     * @throws \RuntimeException when the code is invalid/expired/not applicable.
     */
    public function validate(string $code, User $user, float $subtotalGhs): array
    {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            throw new \RuntimeException('Invalid coupon code.');
        }

        if (! $coupon->isValidFor($user, $subtotalGhs)) {
            throw new \RuntimeException('This coupon is not valid for your order.');
        }

        return [
            'coupon' => $coupon,
            'discount_ghs' => $coupon->calculateDiscount($subtotalGhs),
        ];
    }
}
