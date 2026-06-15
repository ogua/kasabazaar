<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Customer;

use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use App\Http\Resources\Ecommerce\EcommerceCartResource;
use App\Models\EcommerceCart;
use App\Models\EcommerceCartItem;
use App\Models\EcommerceProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcommerceCartController extends CustomerBaseController
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart();

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images'])));
    }

    public function addItem(Request $request): JsonResponse
    {
        $branchId = $this->customerBranchId();

        $data = $request->validate([
            'product_id' => 'required|uuid|exists:ecommerce_products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = EcommerceProduct::where('branch_id', $branchId)
            ->where('is_active', true)
            ->findOrFail($data['product_id']);

        abort_unless($product->branch_id === $branchId, 422, 'Product not available in your branch.');
        abort_unless($product->stock > 0, 422, 'Product is out of stock.');

        $cart = $this->resolveCart();

        $item = EcommerceCartItem::firstOrNew([
            'cart_id' => $cart->id,
            'ecommerce_product_id' => $product->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];

        abort_unless($product->stock >= $newQuantity, 422, "Only {$product->stock} unit(s) available.");

        $item->fill([
            'quantity' => $newQuantity,
            'price_ghs' => $product->discount_price_ghs ?? $product->price_ghs,
        ])->save();

        $cart->update(['expires_at' => now()->addDays(7)]);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images'])), 'Item added to cart.');
    }

    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        $data = $request->validate(['quantity' => 'required|integer|min:0|max:100']);

        $cart = $this->resolveCart();
        $item = EcommerceCartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        if ((int) $data['quantity'] === 0) {
            $item->delete();

            return $this->success(new EcommerceCartResource($cart->load(['items.product.images'])), 'Item removed.');
        }

        $product = EcommerceProduct::find($item->ecommerce_product_id);
        abort_unless($product && ! $product->trashed(), 422, 'Product is no longer available.');
        abort_unless($product->stock >= $data['quantity'], 422, "Only {$product->stock} unit(s) available.");

        $item->update(['quantity' => $data['quantity']]);

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images'])), 'Cart updated.');
    }

    public function removeItem(Request $request, string $itemId): JsonResponse
    {
        $cart = $this->resolveCart();
        $item = EcommerceCartItem::where('cart_id', $cart->id)->findOrFail($itemId);
        $item->delete();

        return $this->success(new EcommerceCartResource($cart->load(['items.product.images'])), 'Item removed.');
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart();
        $cart->items()->delete();

        return $this->success(null, 'Cart cleared.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Coupon functionality is coming soon.',
        ], 501);
    }

    private function resolveCart(): EcommerceCart
    {
        return EcommerceCart::firstOrCreate(
            ['user_id' => auth()->id()],
            ['branch_id' => $this->customerBranchId()]
        );
    }
}
