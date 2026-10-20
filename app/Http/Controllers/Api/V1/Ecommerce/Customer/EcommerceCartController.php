<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceCartResource;
use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceProduct;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCartController extends CustomerBaseController
{
    public function __construct(protected CouponService $couponService) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])));
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid|exists:ecommerce_products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = EcommerceProduct::where('is_active', true)->findOrFail($data['product_id']);

        abort_unless($product->vendor_id && $product->vendor?->status?->value === 'active', 422, 'Product is not available for purchase.');
        abort_unless($product->stock > 0, 422, 'Product is out of stock.');

        $cart = $this->resolveCart($request);

        $item = EcommerceCartItem::firstOrNew([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];

        abort_unless($product->stock >= $newQuantity, 422, "Only {$product->stock} unit(s) available.");

        $item->fill([
            'vendor_id' => $product->vendor_id,
            'quantity' => $newQuantity,
            'price_ghs' => $product->discount_price_ghs ?? $product->price_ghs,
        ])->save();

        $cart->update(['expires_at' => now()->addDays(7)]);
        $this->refreshCoupon($cart);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])), 'Item added to cart.');
    }

    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:100']);

        $cart = $this->resolveCart($request);
        $item = EcommerceCartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        if ((int) $data['quantity'] === 0) {
            $item->delete();
            $this->refreshCoupon($cart);

            return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])), 'Item removed.');
        }

        $product = EcommerceProduct::find($item->ecommerce_product_id);
        abort_unless($product && ! $product->trashed(), 422, 'Product is no longer available.');
        abort_unless($product->stock >= $data['quantity'], 422, "Only {$product->stock} unit(s) available.");

        $item->update(['quantity' => $data['quantity']]);
        $this->refreshCoupon($cart);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])), 'Cart updated.');
    }

    public function removeItem(Request $request, string $itemId): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $item = EcommerceCartItem::where('cart_id', $cart->id)->findOrFail($itemId);
        $item->delete();
        $this->refreshCoupon($cart);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])), 'Item removed.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->items()->delete();
        $cart->update(['coupon_code' => null, 'discount_ghs' => 0]);

        return $this->success(null, 'Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string']);

        $user = auth('sanctum')->user();
        abort_unless($user, 422, 'Please log in to apply a coupon.');

        $cart = $this->resolveCart($request)->load('items');
        $subtotal = (float) $cart->items->sum(fn ($item) => $item->price_ghs * $item->quantity);

        try {
            $result = $this->couponService->validate($data['code'], $user, $subtotal);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $cart->update([
            'coupon_code' => $result['coupon']->code,
            'discount_ghs' => $result['discount_ghs'],
        ]);

        return $this->success(new EcommerceCartResource($cart->fresh(['items.product.images', 'items.vendor'])), 'Coupon applied.');
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->update(['coupon_code' => null, 'discount_ghs' => 0]);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images', 'items.vendor'])), 'Coupon removed.');
    }

    /**
     * Re-prices an applied coupon against the cart's current subtotal, so the
     * discount the cart shows is the one checkout will apply. A coupon the
     * cart no longer qualifies for (e.g. below its minimum spend) is dropped.
     */
    private function refreshCoupon(EcommerceCart $cart): void
    {
        if (! $cart->coupon_code) {
            return;
        }

        $user = auth('sanctum')->user();
        $subtotal = (float) $cart->items()->get()->sum(fn ($item) => $item->price_ghs * $item->quantity);

        if (! $user || $subtotal <= 0) {
            $cart->update(['coupon_code' => null, 'discount_ghs' => 0]);

            return;
        }

        try {
            $result = $this->couponService->validate($cart->coupon_code, $user, $subtotal);
            $cart->update(['discount_ghs' => $result['discount_ghs']]);
        } catch (\RuntimeException) {
            $cart->update(['coupon_code' => null, 'discount_ghs' => 0]);
        }
    }

    /**
     * Guests get a session-keyed cart (gap D.2) via the X-Guest-Session-Id
     * header (kmarket sends its own Laravel session ID); authenticated
     * customers always resolve to their permanent user-keyed cart.
     */
    private function resolveCart(Request $request): EcommerceCart
    {
        $user = auth('sanctum')->user();

        if ($user) {
            return EcommerceCart::firstOrCreate(['user_id' => $user->id]);
        }

        $sessionId = $request->header('X-Guest-Session-Id');
        abort_if(! $sessionId, 422, 'X-Guest-Session-Id header is required.');

        return EcommerceCart::firstOrCreate(['session_id' => $sessionId, 'user_id' => null]);
    }
}
