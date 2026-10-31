<?php

/*
| Where an app's "Update" button sends users when its newest release in
| Admin → Website → Mobile App Releases has no store URL of its own.
| Releases themselves live in the app_versions table, not here.
*/

return [
    'store_urls' => [
        'client' => [
            'android' => env('MOBILE_APP_UPDATE_ANDROID_URL', 'https://play.google.com/store/apps/details?id=com.kasarose.clientapp'),
            'ios' => env('MOBILE_APP_IOS_URL', 'https://apps.apple.com/us/app/kasarose/id6807400170'),
        ],
        'staff' => [
            'android' => env('STAFF_APP_ANDROID_URL', 'https://play.google.com/store/apps/details?id=com.kasabazar.rddshipping'),
            'ios' => env('STAFF_APP_IOS_URL'),
        ],
    ],
];
