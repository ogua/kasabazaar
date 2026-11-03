<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Domain-verification files that let Android (App Links) and iOS (Universal
 * Links) open kasarose.com tracking links in the KASAROSE app when it is
 * installed. Without the app the same links keep opening the website.
 *
 * Only tracking-style paths are claimed: paying from the tracking page stays in
 * the browser, where the phone/email check and Paystack run.
 */
class AppLinksController extends Controller
{
    /**
     * Paths the app opens, in the iOS "components" form.
     *
     * @var list<array<string, mixed>>
     */
    private const IOS_COMPONENTS = [
        ['/' => '/tracking/*/pay*', 'exclude' => true],
        ['/' => '/tracking/payment/*', 'exclude' => true],
        ['/' => '/tracking'],
        ['/' => '/tracking.php'],
        ['/' => '/my-shipment/*'],
        ['/' => '/shipment-label/*'],
    ];

    public function androidAssetLinks(): JsonResponse
    {
        $fingerprints = config('website.mobile_app.android_sha256', []);

        abort_if($fingerprints === [], 404);

        return response()->json([[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => config('website.mobile_app.package'),
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ]], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function appleAppSiteAssociation(): JsonResponse
    {
        $teamId = config('website.mobile_app.ios_team_id');

        abort_if(blank($teamId), 404);

        return response()->json([
            'applinks' => [
                'details' => [[
                    'appIDs' => [$teamId.'.'.config('website.mobile_app.ios_bundle_id')],
                    'components' => self::IOS_COMPONENTS,
                ]],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
