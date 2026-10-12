<?php

return [
    /*
     * Branch whose catalog is shown to unauthenticated (guest/web) marketplace
     * requests — browsing endpoints have no logged-in user to scope by branch_id,
     * so they fall back to this. Authenticated requests (mobile, Bearer token)
     * always use their own branch_id and ignore this value.
     */
    'public_branch_id' => env('ECOMMERCE_PUBLIC_BRANCH_ID'),

    /*
     * Where Paystack/Stripe redirect the browser after a marketplace payment
     * completes. Different client apps need different destinations (the
     * mobile app expects its own deep link, kmarket needs its own web URL),
     * so the caller passes a `client` key (see EcommercePaymentController)
     * that's looked up against this allow-list rather than trusting an
     * arbitrary caller-supplied URL.
     */
    'checkout_callback_urls' => [
        'mobile' => env('ECOMMERCE_MOBILE_CHECKOUT_CALLBACK_URL', env('MOBILE_APP_SCHEME', 'rddshipping').'://payment/complete'),
        'web' => env('KMARKET_CHECKOUT_CALLBACK_URL'),
    ],

    'default_vendor_commission_rate' => (float) env('ECOMMERCE_DEFAULT_VENDOR_COMMISSION_RATE', 10),

    /*
     * The Vendor slug for products sold directly by the platform rather than
     * a third-party vendor (e.g. products created through the admin panel,
     * which has no vendor selector). EcommerceProduct::booted() defaults any
     * product created without a vendor_id to this vendor.
     */
    'platform_vendor_slug' => env('ECOMMERCE_PLATFORM_VENDOR_SLUG', 'kasabazaar-market'),

    /*
     * Auctions. The winner has this many hours after the auction closes to pay
     * through the normal checkout; after that staff may offer the lot again.
     * Bids above max_bid_ghs are rejected as almost certainly mistyped.
     */
    'auction_payment_window_hours' => (int) env('ECOMMERCE_AUCTION_PAYMENT_WINDOW_HOURS', 48),
    'auction_max_bid_ghs' => (float) env('ECOMMERCE_AUCTION_MAX_BID_GHS', 1000000),

    /*
     * When a winner does not pay, the lot is offered to the next-highest
     * bidder up to this many times before it ends as Unpaid. Winners who
     * default this many times can no longer bid (0 disables the ban).
     */
    'auction_max_second_chances' => (int) env('ECOMMERCE_AUCTION_MAX_SECOND_CHANCES', 2),
    'auction_max_payment_defaults' => (int) env('ECOMMERCE_AUCTION_MAX_PAYMENT_DEFAULTS', 2),
];
