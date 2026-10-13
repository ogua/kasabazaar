<?php

/*
|--------------------------------------------------------------------------
| KASAROSE LOGISTICS group
|--------------------------------------------------------------------------
|
| Single source of truth for this marketplace's identity (KASAROSE MARKET),
| for the parent company — KASAROSE LOGISTICS, at kasarose.com, formerly the
| KasaBazaar Group — and for the group companies the storefront links to.
| The roster is mirrored in the sibling repositories; see the "Group of
| Companies" section of CLAUDE.md before editing anything here:
|
|   kasabazaar         config/group.php           (KASAROSE LOGISTICS)
|   neoride            parts/group_companies.php  (Neoride Africa)
|
| Any change to a company name, tagline, URL or legal-entity description must
| be applied to every repository, otherwise the cross-links and the legal
| pages that name the group's companies fall out of sync.
|
*/

$supportEmail = env('SUPPORT_EMAIL') ?: 'support@kasarose.com';

return [

    /*
    |--------------------------------------------------------------------------
    | This company
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => 'KASAROSE MARKET',
        // `legal_entity` is null until the registered company is confirmed —
        // never publish an entity name we have not verified. The "operated by"
        // clauses in the privacy policy, terms and About page are guarded on
        // it and drop the claim entirely when it is unset.
        // `legal_name` is what gets *displayed* (copyright line, legal-page
        // stamp) and falls back to the trading name, which is always true.
        // See the "CONFIRM BEFORE LAUNCH" block in .env.example.
        'legal_entity' => env('GROUP_LEGAL_NAME') ?: null,
        'legal_name' => env('GROUP_LEGAL_NAME') ?: 'KASAROSE MARKET',
        'tagline' => "Ghana's multi-vendor marketplace, delivered.",
        'meta_description' => "KASAROSE MARKET is a multi-vendor marketplace connecting shoppers across Ghana with vetted local vendors, backed by KASAROSE LOGISTICS' shipping and logistics network.",
        'role' => "The group's ecommerce marketplace",
        'url' => env('APP_URL'),
        'founded' => '2025',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parent group
    |--------------------------------------------------------------------------
    */

    // KASAROSE LOGISTICS is the parent company (formerly the KasaBazaar Group;
    // never name KasaBazaar on this site). `name` is the company; `group_name`
    // reads naturally in "part of the …" sentences.
    'parent' => [
        'name' => 'KASAROSE LOGISTICS',
        'group_name' => 'KASAROSE LOGISTICS group',
        'url' => env('GROUP_SITE_URL') ?: 'https://kasarose.com',
        'companies_url' => rtrim(env('GROUP_SITE_URL') ?: 'https://kasarose.com', '/').'/our-companies.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Group companies
    |--------------------------------------------------------------------------
    |
    | Rendered by the storefront footer, the About page and the legal pages
    | that have to name every company in the group.
    |
    */

    'companies' => [
        [
            'key' => 'kasarose-logistics',
            'name' => 'KASAROSE LOGISTICS',
            'role' => 'Parent company — freight, real estate and property management',
            'tagline' => 'Your Trusted Partner for Global Shipments.',
            'description' => "The group's parent company. KASAROSE LOGISTICS runs door-to-door air and ocean freight, ecommerce package forwarding and shipment tracking between the United States and Ghana, alongside real estate and property management. It carries KASAROSE MARKET deliveries that travel beyond a vendor's own delivery area.",
            'url' => env('GROUP_SITE_URL') ?: 'https://kasarose.com',
            'url_label' => 'kasarose.com',
        ],
        [
            'key' => 'neoride-africa',
            'name' => 'Neoride Africa',
            'role' => 'Mobility and last-mile transport',
            'tagline' => "Driving Africa's Mobility Revolution.",
            'description' => 'A tricycle mobility and transport company operating in Ghana since 2025, focused on affordable rides, first- and last-mile connectivity and youth employment. Neoride riders handle a share of KASAROSE MARKET last-mile deliveries in Kumasi and Ejisu.',
            'url' => env('NEORIDE_SITE_URL') ?: 'https://neorideafrica.com',
            'url_label' => 'neorideafrica.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | Phone numbers are the group's published lines.
    |
    */

    'contact' => [
        // Defaults are the group's published support address and phone lines.
        // The legal and privacy roles fall back to the support address so a
        // half-configured environment never publishes a dead contact.
        // `?:` rather than an env() default throughout: a key present but empty
        // in .env resolves to '', which would skip an env() default and publish
        // a blank contact on the legal pages.
        'email' => $supportEmail,
        'legal_email' => env('LEGAL_EMAIL') ?: $supportEmail,
        'privacy_email' => env('PRIVACY_EMAIL') ?: $supportEmail,
        'phone_gh' => env('SUPPORT_PHONE_GH') ?: '+233 50 972 5081',
        'phone_gh_tel' => env('SUPPORT_PHONE_GH_TEL') ?: '+233509725081',
        'phone_us' => env('SUPPORT_PHONE_US') ?: '+1 (574) 440-7460',
        'phone_us_tel' => env('SUPPORT_PHONE_US_TEL') ?: '+15744407460',
        // Null until a registered office is confirmed. Every template that
        // renders it is guarded, so an unset address omits the sentence rather
        // than printing a placeholder.
        'address' => env('SUPPORT_ADDRESS') ?: null,
        'regions' => 'Ghana • United States',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal
    |--------------------------------------------------------------------------
    |
    | `effective_date` stamps every legal page. Bump it whenever the substance
    | of a policy changes, not on typo fixes.
    |
    */

    'legal' => [
        'effective_date' => '13 October 2026',
        'governing_law' => 'the laws of the Republic of Ghana',
        'jurisdiction' => 'the courts of Ghana',
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery timeframes
    |--------------------------------------------------------------------------
    |
    | Published on the delivery policy and the FAQ, and required for payment
    | processor (Paystack/Stripe) compliance.
    |
    */

    'delivery' => [
        [
            'service' => 'Same-city delivery (Accra, Kumasi)',
            'estimate' => '1–3 business days',
            'notes' => 'Dispatched by the vendor once payment clears, delivered by our own riders or a partner courier.',
        ],
        [
            'service' => 'Nationwide delivery (other regions)',
            'estimate' => '3–7 business days',
            'notes' => 'Routed through the group\'s logistics network, including KASAROSE LOGISTICS road freight where required.',
        ],
        [
            'service' => 'Bulky or oversized items',
            'estimate' => '5–10 business days',
            'notes' => 'Furniture, appliances and anything requiring a dedicated vehicle. The vendor confirms a delivery window before dispatch.',
        ],
        [
            'service' => 'Items shipped from abroad',
            'estimate' => '10–21 business days',
            'notes' => 'Handled by KASAROSE LOGISTICS. Measured from the day the item reaches our US warehouse, not from the day you ordered it.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Returns
    |--------------------------------------------------------------------------
    */

    'returns' => [
        'window_days' => 7,
        'refund_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auctions
    |--------------------------------------------------------------------------
    |
    | Published in the Terms of Use and on the auction pages. The backend
    | enforces the real window (ECOMMERCE_AUCTION_PAYMENT_WINDOW_HOURS in the
    | kasabazaar app) — keep the two in step.
    |
    */

    'auctions' => [
        'payment_window_hours' => (int) (env('AUCTION_PAYMENT_WINDOW_HOURS') ?: 48),
    ],

];
