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
        'mobile' => env('ECOMMERCE_MOBILE_CHECKOUT_CALLBACK_URL', 'rdd-client://payment/complete'),
        'web' => env('KMARKET_CHECKOUT_CALLBACK_URL'),
    ],

    'default_vendor_commission_rate' => (float) env('ECOMMERCE_DEFAULT_VENDOR_COMMISSION_RATE', 10),
];
