<?php

/*
|--------------------------------------------------------------------------
| KASAROSE Group of Companies
|--------------------------------------------------------------------------
|
| Single source of truth for KASAROSE LOGISTICS' place in the group and for the
| sister companies its public website links out to. KASAROSE (the marketplace)
| is the parent company. The same roster is mirrored in the sibling
| repositories — see the "Group of Companies" section of CLAUDE.md before
| editing anything here:
|
|   kmarket            config/group.php           (KASAROSE, parent)
|   neoride            parts/group_companies.php  (Neoride Africa)
|
| Any change to a company name, tagline, URL or legal-entity description must
| be applied to all three, otherwise the cross-links and the legal pages that
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
        'name' => 'KASAROSE LOGISTICS',
        'full_name' => 'KASAROSE LOGISTICS',
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

    // KASAROSE is the parent company; the group has no separate website, so the
    // parent links resolve to the marketplace and its Our Group page.
    'parent' => [
        'name' => 'KASAROSE Group of Companies',
        'url' => env('KASAROSE_SITE_URL') ?: 'https://kasarose.com',
        'companies_url' => rtrim(env('KASAROSE_SITE_URL') ?: 'https://kasarose.com', '/').'/our-group',
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
    | Sister companies
    |--------------------------------------------------------------------------
    |
    | Rendered by resources/views/web/footer.blade.php and named in the
    | privacy policy.
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
        ],
        [
            'key' => 'kasarose',
            'name' => 'KASAROSE',
            'role' => 'Parent company and ecommerce marketplace',
            'description' => "The group's parent company and multi-vendor marketplace, where vetted Ghanaian vendors sell direct to shoppers nationwide. KASAROSE LOGISTICS carries a share of KASAROSE deliveries — nationwide road freight and anything arriving from the US warehouse.",
            // CONFIRM BEFORE LAUNCH: kasarose.com is the intended marketplace
            // domain but is not yet verified as live. Override with
            // KASAROSE_SITE_URL rather than editing the fallback.
            'url' => env('KASAROSE_SITE_URL') ?: 'https://kasarose.com',
            'url_label' => 'kasarose.com',
        ],
    ],

];
