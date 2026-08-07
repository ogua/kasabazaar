<div class="dropdown cart-dropdown mr-0 mr-lg-2" wire:key="cart-icon">
    <div class="cart-overlay"></div>
    <a href="{{ route('storefront.cart') }}" class="cart-toggle label-down link">
        <i class="w-icon-cart">
            <span class="cart-count">{{ $count }}</span>
        </i>
        <span class="cart-label">Cart</span>
    </a>
    <div class="dropdown-box">
        <div class="cart-total">
            <label>Subtotal:</label>
            <span class="price">GHS {{ number_format($subtotalGhs, 2) }}</span>
        </div>
        <div class="cart-action">
            <a href="{{ route('storefront.cart') }}" class="btn btn-dark btn-outline btn-rounded">View Cart</a>
            <a href="{{ route('storefront.checkout') }}" class="btn btn-primary btn-rounded">Checkout</a>
        </div>
    </div>
</div>
