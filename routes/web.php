<?php

use App\Http\Controllers\SitemapController;
use App\Livewire\Storefront;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// ─── Storefront (public + customer) ────────────────────────────────────────
Route::name('storefront.')->group(function () {
    Route::get('/', Storefront\Home::class)->name('home');
    Route::get('/shop', Storefront\ProductListing::class)->name('shop');
    Route::get('/category/{category}', Storefront\ProductListing::class)->name('category');
    Route::get('/product/{product}', Storefront\ProductDetail::class)->name('product');

    Route::get('/vendors', Storefront\VendorDirectory::class)->name('vendors');
    Route::get('/vendor/{vendor}', Storefront\VendorStorePage::class)->name('vendor');
    Route::get('/become-a-vendor', Storefront\BecomeAVendor::class)->name('become-vendor');

    Route::get('/cart', Storefront\CartPage::class)->name('cart');
    Route::get('/checkout', Storefront\Checkout::class)->name('checkout')->middleware('auth');
    Route::get('/checkout/callback', Storefront\CheckoutCallback::class)->name('checkout.callback')->middleware('auth');
    Route::get('/wishlist', Storefront\WishlistPage::class)->name('wishlist');

    Route::get('/login', Storefront\Auth\Login::class)->name('login')->middleware('guest');
    Route::get('/register', Storefront\Auth\Register::class)->name('register')->middleware('guest');
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('storefront.home');
    })->name('logout')->middleware('auth');
    Route::get('/forgot-password', Storefront\Auth\ForgotPassword::class)->name('password.request')->middleware('guest');
    Route::get('/reset-password/{token}', Storefront\Auth\ResetPassword::class)->name('password.reset')->middleware('guest');

    Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
        Route::get('/', Storefront\Account\Dashboard::class)->name('dashboard');
        Route::get('/orders', Storefront\Account\Orders::class)->name('orders');
        Route::get('/orders/{order}', Storefront\Account\OrderDetail::class)->name('orders.show');
        Route::get('/addresses', Storefront\Account\Addresses::class)->name('addresses');
        Route::get('/profile', Storefront\Account\Profile::class)->name('profile');
    });

    Route::get('/blog', fn () => view('storefront.placeholder', ['title' => 'Blog']))->name('blog');
    Route::get('/about-us', fn () => view('storefront.pages.about'))->name('about');
    Route::get('/contact-us', fn () => view('storefront.pages.contact'))->name('contact');
    Route::get('/faq', fn () => view('storefront.pages.faq'))->name('faq');
});

// ─── Vendor self-service dashboard ─────────────────────────────────────────
// Uses the same local `users` table/guard as the storefront (a vendor is a
// User with role=vendor) — the `vendor.role` middleware gates access rather
// than a separate auth guard.
use App\Livewire\Vendor as VendorArea;
use Illuminate\Support\Facades\Auth;

Route::prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/login', VendorArea\Auth\Login::class)->name('login');

    Route::middleware(['auth', 'vendor.role'])->group(function () {
        Route::get('/', VendorArea\Overview::class)->name('dashboard');
        Route::get('/products', VendorArea\Products\Index::class)->name('products.index');
        Route::get('/products/create', VendorArea\Products\Form::class)->name('products.create');
        Route::get('/products/{product}/edit', VendorArea\Products\Form::class)->name('products.edit');
        Route::get('/orders', VendorArea\Orders\Index::class)->name('orders.index');
        Route::get('/orders/{order}', VendorArea\Orders\Show::class)->name('orders.show');
        Route::get('/earnings', VendorArea\Earnings\Index::class)->name('earnings');
        Route::get('/store-settings', VendorArea\StoreSettings::class)->name('store-settings');
        Route::get('/reviews', VendorArea\Reviews\Index::class)->name('reviews');
        Route::get('/coupons', VendorArea\Coupons\Index::class)->name('coupons.index');
        Route::post('/logout', function () {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('vendor.login');
        })->name('logout');
    });
});
