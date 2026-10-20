<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Header\CartIcon;
use App\Services\Kasabazaar\CartApi;
use App\Services\Kasabazaar\KasabazaarApiException;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPage extends Component
{
    /** The backend's per-line quantity ceiling (EcommerceCartController). */
    public const MAX_QUANTITY = 100;

    public array $cart = [];

    public string $couponCode = '';

    public string $couponError = '';

    public ?string $error = null;

    /**
     * Bumped when a quantity change is rejected, so the quantity inputs are
     * re-rendered with the cart's real value instead of keeping what was typed.
     */
    public int $quantityRevision = 0;

    public function mount(CartApi $cartApi): void
    {
        $this->load($cartApi);
    }

    /**
     * Reloads when something outside this page changes the cart (e.g. a
     * product card's add-to-cart). This page's own changes use the cart the
     * API returns and do not trigger it.
     */
    #[On('cart-updated')]
    public function load(CartApi $cartApi): void
    {
        try {
            $this->cart = $cartApi->show();
            $this->error = null;
        } catch (KasabazaarApiException $e) {
            Log::warning('storefront.cart: failed to load cart', ['message' => $e->getMessage()]);
            $this->error = 'We\'re having trouble loading your cart right now. Please refresh or try again shortly.';
        }
    }

    public function updateQuantity(CartApi $cartApi, string $itemId, mixed $quantity): void
    {
        $item = collect($this->cart['items'] ?? [])->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        $requested = filter_var($quantity, FILTER_VALIDATE_INT);
        $limit = $this->quantityLimit($item);

        if ($requested === false || $requested < 1) {
            $this->rejectQuantity('Enter a quantity of 1 or more. To take an item out, use Remove.');

            return;
        }

        if ($requested > $limit) {
            $this->rejectQuantity($limit === self::MAX_QUANTITY
                ? 'You can order up to '.self::MAX_QUANTITY.' of an item at a time.'
                : "Only {$limit} of this item ".($limit === 1 ? 'is' : 'are').' available.');

            return;
        }

        if ($requested === (int) $item['quantity']) {
            return;
        }

        try {
            $this->cart = $cartApi->updateItem($itemId, $requested);
            $this->notifyHeader();
        } catch (KasabazaarApiException $e) {
            $this->rejectQuantity($e->getMessage());
        }
    }

    public function removeItem(CartApi $cartApi, string $itemId): void
    {
        try {
            $this->cart = $cartApi->removeItem($itemId);
            $this->notifyHeader();
            $this->dispatch('toast', type: 'success', message: 'Item removed from cart.');
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function applyCoupon(CartApi $cartApi): void
    {
        $this->couponCode = trim($this->couponCode);

        if ($this->couponCode === '') {
            $this->couponError = 'Enter a coupon code.';

            return;
        }

        try {
            $this->cart = $cartApi->applyCoupon($this->couponCode);
            $this->couponCode = '';
            $this->couponError = '';
        } catch (KasabazaarApiException $e) {
            $this->couponError = $e->getMessage();
        }
    }

    public function removeCoupon(CartApi $cartApi): void
    {
        try {
            $this->cart = $cartApi->removeCoupon();
            $this->couponError = '';
        } catch (KasabazaarApiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Lines checkout would reject, keyed by item id: products since removed
     * or delisted, and quantities above current stock. Checkout stays locked
     * until they are fixed, so the shopper learns here rather than at payment.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function problems(): array
    {
        return self::problemsIn($this->cart);
    }

    /**
     * @return array<string, string>
     */
    public static function problemsIn(array $cart): array
    {
        return collect($cart['items'] ?? [])
            ->mapWithKeys(function (array $item): array {
                if (($item['is_available'] ?? true) === false) {
                    return [$item['id'] => 'No longer available. Remove it to continue.'];
                }

                $stock = isset($item['stock']) ? (int) $item['stock'] : null;

                if ($stock !== null && $stock <= 0) {
                    return [$item['id'] => 'Sold out. Remove it to continue.'];
                }

                if ($stock !== null && $item['quantity'] > $stock) {
                    return [$item['id'] => "Only {$stock} left. Lower the quantity to continue."];
                }

                return [];
            })
            ->all();
    }

    public function quantityLimit(array $item): int
    {
        $stock = isset($item['stock']) ? (int) $item['stock'] : null;

        return $stock === null ? self::MAX_QUANTITY : max(1, min($stock, self::MAX_QUANTITY));
    }

    private function rejectQuantity(string $message): void
    {
        $this->quantityRevision++;
        $this->dispatch('toast', type: 'error', message: $message);
    }

    private function notifyHeader(): void
    {
        $this->dispatch('cart-updated')->to(CartIcon::class);
    }

    public function render()
    {
        return view('livewire.storefront.cart-page')->layout('storefront.layouts.app', ['title' => 'Shopping Cart']);
    }
}
