<?php

/*
|--------------------------------------------------------------------------
| KasaBazaar Group of Companies
|--------------------------------------------------------------------------
|
| Single source of truth for RDD Shipping's place in the group and for the
| sister companies its public website links out to. The same roster is
| mirrored in the other three repositories — see the "Group of Companies"
| section of CLAUDE.md before editing anything here:
|
|   kasabazar-website  includes/config.php        ($group_companies)
|   kmarket            config/group.php           (KASAROSE)
|   neoride            parts/group_companies.php  (Neoride Africa)
|
| Any change to a company name, tagline, URL or legal-entity description must
| be applied to all four, otherwise the cross-links and the legal pages that
| name the group's companies fall out of sync.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | This company
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => 'RDD Shipping',
        'full_name' => 'Rose Door to Door Shipping & Delivery Service',
        'legal_name' => env('GROUP_LEGAL_NAME') ?: 'KasaBazaar Limited',
        'tagline' => 'Your Trusted Partner for Global Shipments.',
        'role' => "The group's freight logistics arm",
        'url' => env('APP_URL') ?: 'https://rddshipping.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parent group
    |--------------------------------------------------------------------------
    */

    'parent' => [
        'name' => 'KasaBazaar Group of Companies',
        'url' => env('GROUP_SITE_URL') ?: 'https://kasabazaar.com',
        'companies_url' => (env('GROUP_SITE_URL') ?: 'https://kasabazaar.com').'/our-companies.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sister companies
    |--------------------------------------------------------------------------
    |
    | Rendered by resources/views/web/footer.blade.php and named in the
    | privacy policy.
    |
    */

    'companies' => [
        [
            'key' => 'kasabazaar',
            'name' => 'KasaBazaar',
            'role' => 'Import & export, real estate and property management',
            'description' => "The group's founding company, offering door-to-door import & export shipping alongside real estate sales and property management services.",
            'url' => env('GROUP_SITE_URL') ?: 'https://kasabazaar.com',
            'url_label' => 'kasabazaar.com',
        ],
        [
            'key' => 'neoride-africa',
            'name' => 'Neoride Africa',
            'role' => 'Mobility and last-mile transport',
            'description' => 'A tricycle mobility and transport company operating in Ghana since 2025, focused on affordable rides, first- and last-mile connectivity and youth employment.',
            'url' => env('NEORIDE_SITE_URL') ?: 'https://neorideafrica.com',
            'url_label' => 'neorideafrica.com',
        ],
        [
            'key' => 'kasarose',
            'name' => 'KASAROSE',
            'role' => 'Ecommerce marketplace',
            'description' => "The group's multi-vendor marketplace, where vetted Ghanaian vendors sell direct to shoppers nationwide. RDD Shipping carries a share of KASAROSE deliveries — nationwide road freight and anything arriving from the US warehouse.",
            // CONFIRM BEFORE LAUNCH: kasarose.com is the intended marketplace
            // domain but is not yet verified as live. Override with
            // KASAROSE_SITE_URL rather than editing the fallback.
            'url' => env('KASAROSE_SITE_URL') ?: 'https://kasarose.com',
            'url_label' => 'kasarose.com',
        ],
    ],

];
