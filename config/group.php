<?php

/*
|--------------------------------------------------------------------------
| KASAROSE LOGISTICS group
|--------------------------------------------------------------------------
|
| KASAROSE LOGISTICS (formerly the KasaBazaar Group) is the group's parent
| company. This app serves its whole web presence on kasarose.com: the
| public website (resources/views/site, ported from the retired plain-PHP
| "KasaRose Website" repo in October 2026) and the back office. The group
| companies its pages link to are mirrored in the sibling repositories — see
| the "Group of Companies" section of CLAUDE.md before editing anything here:
|
|   kmarket            config/group.php           (KASAROSE MARKET)
|   neoride            parts/group_companies.php  (Neoride Africa)
|
| Any change to a company name, tagline, URL or legal-entity description must
| be applied to all of them, otherwise the cross-links and the legal pages
| that name the group's companies fall out of sync.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | This company
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => 'KASAROSE LOGISTICS',
        'full_name' => 'KASAROSE LOGISTICS',
        'legal_name' => env('GROUP_LEGAL_NAME') ?: 'KasaBazaar Limited',
        'tagline' => 'Your Trusted Partner for Global Shipments.',
        'role' => 'Parent company of the group',
        'url' => env('APP_URL') ?: 'https://kasarose.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parent group
    |--------------------------------------------------------------------------
    */

    // KASAROSE LOGISTICS is itself the parent: `url` is its main website.
    'parent' => [
        'name' => 'KASAROSE LOGISTICS',
        'group_name' => 'KASAROSE LOGISTICS group',
        // The parent's own card on the public site's "Our Companies" page and homepage teaser.
        'tagline' => 'Door-to-door freight, real estate and property management.',
        'description' => 'The group\'s founding company — known as KasaBazaar until 2026, and incorporating the door-to-door freight business formerly run as RDD Shipping. Air and sea freight, ecommerce package forwarding and shipment tracking between the United States and Ghana, alongside real estate sales and property management.',
        'logo' => 'images/kasarose-logistics-logo.png',
        'url' => env('GROUP_SITE_URL') ?: 'https://kasarose.com',
        'companies_url' => rtrim(env('GROUP_SITE_URL') ?: 'https://kasarose.com', '/').'/our-companies.php',
        // Public tracking page; invoices and receipts link to it as tracking?ref={tracking_number}.
        // Documents printed before October 2026 carry tracking.php, which 301-redirects here.
        'tracking_url' => rtrim(env('GROUP_SITE_URL') ?: 'https://kasarose.com', '/').'/tracking',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS programme terms
    |--------------------------------------------------------------------------
    |
    | Public page documenting the SMS notification programme, referenced by the
    | privacy policy. It is the URL registered with the messaging carriers, so
    | it is kept as its own setting rather than derived from the parent group —
    | point SMS_TERMS_URL at a new page only after the carrier registration has
    | been updated to match.
    |
    */

    'sms_terms_url' => env('SMS_TERMS_URL') ?: 'https://kasabazaar.com/sms-terms.php',

    /*
    |--------------------------------------------------------------------------
    | Group companies
    |--------------------------------------------------------------------------
    |
    | Rendered by the public site (resources/views/site) and named in its
    | privacy policy and terms. `show_on_website` => false keeps a real sister
    | company off the public pages without removing it from the roster.
    |
    */

    'companies' => [
        [
            'key' => 'neoride-africa',
            'name' => 'Neoride Africa',
            'role' => 'Mobility and last-mile transport',
            'description' => 'A tricycle mobility and transport company operating in Ghana since 2025, focused on affordable rides, first- and last-mile connectivity and youth employment.',
            'url' => env('NEORIDE_SITE_URL') ?: 'https://neorideafrica.com',
            'url_label' => 'neorideafrica.com',
            // Deliberately not shown on kasarose.com since 2026-09.
            'show_on_website' => false,
        ],
        [
            'key' => 'kasarose-market',
            'name' => 'KASAROSE MARKET',
            'role' => 'Ecommerce marketplace',
            'description' => "The group's multi-vendor marketplace, where vetted Ghanaian vendors sell direct to shoppers nationwide. KASAROSE LOGISTICS carries a share of KASAROSE MARKET deliveries — nationwide road freight and anything arriving from the US warehouse.",
            // CONFIRM BEFORE LAUNCH: kasarosemarket.com is assumed from the
            // marketplace's name and not yet verified. Override with
            // MARKET_SITE_URL rather than editing the fallback.
            'url' => env('MARKET_SITE_URL') ?: 'https://kasarosemarket.com',
            'url_label' => 'kasarosemarket.com',
            'show_on_website' => true,
            'tagline' => "Ghana's multi-vendor marketplace, delivered.",
            'logo' => 'images/kasarosemarket-logo.png',
        ],
    ],

];
