<?php

/*
|--------------------------------------------------------------------------
| Public website (kasarose.com)
|--------------------------------------------------------------------------
|
| The KASAROSE LOGISTICS public site, served by this app from
| resources/views/site since October 2026. It was ported from the
| plain-PHP "KasaRose Website" repo (includes/config.php), which is no
| longer deployed. Company names and sister-company URLs still come from
| config/group.php — only the site's own contact details and copy data
| live here.
|
| The published phone numbers, support email and address are mirrored in
| the other group repos and on the letterheads — see "Group of Companies"
| in CLAUDE.md before changing any of them.
|
*/

return [

    'contact' => [
        // Each number has a display form and a dialable `_tel` form — keep the pair in step.
        'phone_us_1' => '+1 (574) 440-7460',
        'phone_us_1_tel' => '+15744407460',
        'phone_us_2' => '+1 (773) 970-0129',
        'phone_us_2_tel' => '+17739700129',
        'phone_gh_1' => '+233 54 517 0568',
        'phone_gh_1_tel' => '+233545170568',
        'phone_gh_2' => '+233 50 972 5081',
        'phone_gh_2_tel' => '+233509725081',
        'email_support' => 'support@kasarose.com',
        'regions' => 'United States &bull; Ghana',
        'address_gh' => 'Adako Jachie, Ejisu, Kumasi',
        'map_embed' => 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3962.377613898537!2d-1.510474!3d6.723694!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfdbeac7c67646f1%3A0x7f50d40c195153d1!2sAdako%20Jachie%20Rd%2C%20Ejisu%2C%20Ghana!5e0!3m2!1sen!2sus!4v1737047181287!5m2!1sen!2sus',
    ],

    'staff_mail_url' => 'https://mail.hostinger.com',

    /*
    | The photo on the tracking page's "Get live support" card, relative to public/site.
    | Temporary illustration — drop the real photo in images/support and change this path.
    */
    'support_image' => 'images/support/agent-placeholder.svg',

    /*
    | The KASAROSE customer app. The Android link is the Play testing opt-in page;
    | set MOBILE_APP_ANDROID_URL to the public store listing once it is in production.
    */
    'mobile_app' => [
        'android_url' => env('MOBILE_APP_ANDROID_URL', 'https://play.google.com/apps/testing/com.kasarose.clientapp'),
        'ios_url' => env('MOBILE_APP_IOS_URL', 'https://apps.apple.com/us/app/kasarose/id6807400170'),
        'ios_app_id' => env('MOBILE_APP_IOS_ID', '6807400170'),

        // App Links / Universal Links: tracking links on kasarose.com open in the app
        // when it is installed. Served at /.well-known/assetlinks.json and
        // /.well-known/apple-app-site-association; nothing is published until the
        // fingerprints / team id are set.
        'package' => env('MOBILE_APP_PACKAGE', 'com.kasarose.clientapp'),
        // Comma-separated SHA-256 signing-cert fingerprints: the EAS upload key AND the
        // Play App Signing key (Play Console > App integrity).
        'android_sha256' => array_values(array_filter(array_map('trim', explode(',', (string) env('MOBILE_APP_ANDROID_SHA256', ''))))),
        'ios_team_id' => env('MOBILE_APP_IOS_TEAM_ID'),
        'ios_bundle_id' => env('MOBILE_APP_IOS_BUNDLE_ID', 'com.kasarose.clientapp'),
    ],

    /*
    | Every inbox that receives a copy of a website form submission (contact, quote,
    | feedback). Submissions are also stored as Contact Messages / Customer Feedback.
    */
    'form_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) (env('WEBSITE_FORM_RECIPIENTS') ?: 'kasabazaar109@gmail.com,support@kasarose.com,support@kasabazaar.com'))))),

    'quote_response_time' => 'within 1 business day',

    // Published on the delivery policy, FAQ and quote pages; required for payment-processor compliance.
    'delivery_timeframes' => [
        [
            'service' => 'Air Freight',
            'estimate' => '7&ndash;14 business days',
            'notes' => 'Door-to-door between the United States and Ghana, from collection to final delivery.',
        ],
        [
            'service' => 'Ocean Freight',
            'estimate' => '45&ndash;60 days',
            'notes' => 'Port-to-port sailing time plus customs clearance and inland delivery.',
        ],
        [
            'service' => 'Road Freight &amp; Last-Mile Delivery',
            'estimate' => '1&ndash;3 business days',
            'notes' => 'Local and regional transport once cargo has cleared the port or left our warehouse.',
        ],
        [
            'service' => 'Ecommerce Package Forwarding',
            'estimate' => '10&ndash;21 business days',
            'notes' => 'Measured from the day your package is received at our US address, not from the date you ordered it from the retailer.',
        ],
    ],

    /*
    | The services, in the order they appear on the services page. Drives the footer,
    | the canvas menu and the homepage teaser, all linking to /our-services#{slug}.
    */
    'services' => [
        'air-freight' => [
            'label' => 'Air Freight',
            'tagline' => 'Fast, reliable air freight for time-sensitive international shipments.',
            'icon' => 'fa-plane-up',
            'thumb' => 'images/serv-s3.png',
        ],
        'ocean-freight' => [
            'label' => 'Ocean Freight',
            'tagline' => 'Cost-effective sea freight for bulk and oversized shipments.',
            'icon' => 'fa-ship',
            'thumb' => 'images/serv-s6.png',
        ],
        'road-freight' => [
            'label' => 'Road Freight',
            'tagline' => 'Dependable road transport and last-mile delivery for local shipments.',
            'icon' => 'fa-truck-fast',
            'thumb' => 'images/serv-s5.png',
        ],
        'warehousing' => [
            'label' => 'Warehousing',
            'tagline' => 'Secure storage and inventory support — coming soon from our group.',
            'icon' => 'fa-warehouse',
            'thumb' => 'images/serv-s9.png',
            'badge' => 'Coming Soon',
        ],
        'ecommerce-logistics' => [
            'label' => 'Ecommerce Logistics',
            'tagline' => 'Package forwarding for Amazon, eBay, Walmart and Best Buy purchases.',
            'icon' => 'fa-boxes-packing',
            'thumb' => 'images/serv-s4.png',
        ],
    ],

    /*
    | Banner behind the page title on every sub-page (and the 404 and shipment portal),
    | relative to public/site. The theme lays a 60% navy overlay over it and puts the
    | title on the left, so the subject belongs on the right.
    */
    'page_banner' => 'images/hero/page-banner.jpg',

    /*
    | Homepage hero, in slide order. `image` is relative to public/site; photos are
    | 1600px-wide JPGs in images/hero with the subjects in the right two-thirds, since
    | the copy panel covers the left of the frame.
    | `layout` is "scene" (full-bleed photo) or "poster" (a portrait flyer beside the copy).
    */
    'hero_slides' => [
        [
            'step' => 'Pickup',
            'step_note' => 'Across the US',
            'layout' => 'scene',
            'image' => 'images/hero/pickup-usa.jpg',
            'alt' => 'A KASAROSE LOGISTICS courier in branded uniform collecting a parcel from a home in the United States',
            'eyebrow' => 'Ship from the USA to Ghana',
            'heading' => 'Their doorstep in America. <em>Your door in Ghana.</em>',
            'text' => 'Book a pickup anywhere in the US. Our uniformed crew collects, ships and hands it over in Ghana &mdash; tracked every mile.',
            'cta' => ['label' => 'Book a Pickup', 'route' => 'quote'],
        ],
        [
            'step' => 'Ship',
            'step_note' => 'By air &amp; sea',
            'layout' => 'scene',
            'image' => 'images/hero/shipping-usa.jpg',
            'alt' => 'KASAROSE LOGISTICS warehouse staff scanning and loading labelled boxes at the US warehouse',
            'eyebrow' => 'Checked, scanned &amp; shipped',
            'heading' => 'Every box scanned. <em>Every mile tracked.</em>',
            'text' => 'Packed and labelled at our US warehouse, then flown or shipped to Ghana by air or sea &mdash; with status updates by SMS, email and the app.',
            'cta' => ['label' => 'Get a Quote', 'route' => 'quote'],
        ],
        [
            'step' => 'Deliver',
            'step_note' => 'Anywhere in Ghana',
            'layout' => 'scene',
            'image' => 'images/hero/delivery-ghana.jpg',
            'alt' => 'A KASAROSE LOGISTICS courier in branded uniform handing a parcel to a customer at her gate in Ghana',
            'eyebrow' => 'Delivered to the door in Ghana',
            'heading' => 'Accra, Kumasi &amp; beyond. <em>Right to your door.</em>',
            'text' => 'No port queues, no chasing. Our Ghana crew brings your package to your home or business and gets a signature on handover.',
            'cta' => ['label' => 'Track a Shipment', 'route' => 'our-tracking'],
        ],
        [
            'step' => 'Doorstep',
            'step_note' => 'Signed &amp; smiling',
            'layout' => 'scene',
            'image' => 'images/hero/delivery-ghana-family.jpg',
            'alt' => 'A KASAROSE LOGISTICS courier handing a parcel to a smiling mother and daughter at their front door in Ghana',
            'eyebrow' => 'Sent with love from the USA',
            'heading' => 'Packed in America. <em>Opened with a smile in Ghana.</em>',
            'text' => 'Gifts, groceries, electronics and everything in between &mdash; carried from your family in the US to your family in Ghana.',
            'cta' => ['label' => 'Send a Package', 'route' => 'quote'],
        ],
        [
            'step' => 'Home to Home',
            'step_note' => 'Door to door',
            'layout' => 'poster',
            'image' => 'images/hero/usa-to-ghana-flyer.jpg',
            'alt' => 'KASAROSE LOGISTICS flyer: Send your packages door-to-door from USA to Ghana — from US business to Ghana business, from US home to Ghana home',
            'eyebrow' => 'Your trusted partner in Ghana shipping',
            'heading' => 'Home to home. <em>Business to business.</em>',
            'text' => 'From a US home to a Ghana home, or a US business to a Ghana business &mdash; one call on either side of the Atlantic gets your packages moving.',
            'cta' => ['label' => 'Get a Quote', 'route' => 'quote'],
        ],
    ],

    /*
    | Feedback categories offered on the public feedback form — the back-office list
    | (CustomerFeedback::CATEGORIES) minus the mobility-only ones, which belong to Neoride.
    */
    'feedback_categories' => [
        'Delivery Speed',
        'Item Condition',
        'Late Delivery',
        'Wrong Address',
        'Damaged Item',
        'Damaged Packaging',
        'Wrong Item Sent',
        'Missing Item',
        'Ignored Instructions',
        'Other',
    ],

];
