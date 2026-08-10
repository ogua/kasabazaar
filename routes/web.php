<?php

use App\Http\Controllers\SitemapController;
use App\Livewire\Storefront;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// ─── Storefront (public + customer) ────────────────────────────────────────
// Note: the vendor self-service dashboard lives in the `vendor` Filament
// panel (see App\Providers\Filament\VendorPanelProvider), not here.
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
    Route::get('/track-order', Storefront\TrackOrder::class)->name('track-order');

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

    Route::get('/about-us', fn () => view('storefront.pages.about'))->name('about');
    Route::get('/contact-us', Storefront\Contact::class)->name('contact');
    Route::get('/faq', fn () => view('storefront.pages.faq'))->name('faq');
});
