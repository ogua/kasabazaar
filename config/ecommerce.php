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
        'mobile' => env('ECOMMERCE_MOBILE_CHECKOUT_CALLBACK_URL', env('MOBILE_APP_SCHEME', 'kasaroseclient').'://payment/complete'),
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

    /*
     * Days after delivery within which a customer can request a return. Published in
     * the Returns policy on the storefront (config/group.php there) — keep them in step.
     */
    'return_window_days' => (int) env('ECOMMERCE_RETURN_WINDOW_DAYS', 7),

    /*
     * Vendor delivery fee (GHS) charged when no shipping method is configured for
     * the buyer's region, so delivery is never silently free. CONFIRM before launch.
     */
    'default_delivery_fee_ghs' => (float) env('ECOMMERCE_DEFAULT_DELIVERY_FEE_GHS', 20),

    /*
     * KASAROSE USA-to-Ghana shipping for items held in the USA. The lane is
     * fixed, so distance is built into the per-kg rate rather than computed per
     * order. CONFIRM the rate with KASAROSE LOGISTICS before launch. Weight
     * charged = item weight x quantity (default_weight_kg when an item has none),
     * and the charge is never below min_charge_usd.
     */
    'kasarose_shipping' => [
        'rate_per_kg_usd' => (float) env('ECOMMERCE_KASAROSE_RATE_PER_KG_USD', 10),
        'min_charge_usd' => (float) env('ECOMMERCE_KASAROSE_MIN_CHARGE_USD', 15),
        'default_weight_kg' => (float) env('ECOMMERCE_KASAROSE_DEFAULT_WEIGHT_KG', 1),
        'min_days' => (int) env('ECOMMERCE_KASAROSE_MIN_DAYS', 14),
        'max_days' => (int) env('ECOMMERCE_KASAROSE_MAX_DAYS', 28),
    ],

    /*
     * Buyer memberships. The WhatsApp group is for buyers to discuss products
     * and share information only (never for selling); its invite link is shown
     * just to members with a running membership.
     */
    'membership' => [
        'whatsapp_buyers_group_url' => env('ECOMMERCE_BUYERS_WHATSAPP_GROUP_URL'),
    ],
];
