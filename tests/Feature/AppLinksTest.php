<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppLinksTest extends TestCase
{
    public function test_nothing_is_published_until_configured(): void
    {
        config(['website.mobile_app.android_sha256' => [], 'website.mobile_app.ios_team_id' => null]);

        $this->get('/.well-known/assetlinks.json')->assertNotFound();
        $this->get('/.well-known/apple-app-site-association')->assertNotFound();
    }

    public function test_android_asset_links_name_the_app_and_its_fingerprints(): void
    {
        config(['website.mobile_app.android_sha256' => ['AA:BB:CC']]);

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('0.target.package_name', 'com.kasarose.clientapp')
            ->assertJsonPath('0.target.sha256_cert_fingerprints.0', 'AA:BB:CC');
    }

    public function test_ios_association_claims_tracking_but_not_payment_pages(): void
    {
        config(['website.mobile_app.ios_team_id' => 'TEAM123']);

        $components = $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertJsonPath('applinks.details.0.appIDs.0', 'TEAM123.com.kasarose.clientapp')
            ->json('applinks.details.0.components');

        $this->assertContains(['/' => '/my-shipment/*'], $components);
        $this->assertContains(['/' => '/tracking/*/pay*', 'exclude' => true], $components);
        $this->assertSame(['/' => '/tracking/*/pay*', 'exclude' => true], $components[0], 'Exclusions must come before the paths they carve out of.');
    }
}
