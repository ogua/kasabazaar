<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyDomainRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'group.parent.url' => 'https://kasarose.com',
            'group.legacy_domains' => ['rddshipping.com', 'www.rddshipping.com'],
        ]);
    }

    public function test_old_domain_page_redirects_to_same_path_and_query(): void
    {
        $this->get('http://rddshipping.com/tracking.php?ref=ABC123')
            ->assertStatus(301)
            ->assertRedirect('https://kasarose.com/tracking.php?ref=ABC123');
    }

    public function test_www_and_mixed_case_hosts_redirect(): void
    {
        $this->get('https://WWW.RDDShipping.com/news-detail.php?slug=hello')
            ->assertStatus(301)
            ->assertRedirect('https://kasarose.com/news-detail.php?slug=hello');
    }

    public function test_old_domain_root_redirects_to_homepage(): void
    {
        $this->get('http://rddshipping.com/')
            ->assertStatus(301)
            ->assertRedirect('https://kasarose.com/');
    }

    public function test_api_on_old_domain_is_not_redirected(): void
    {
        $response = $this->getJson('http://rddshipping.com/api/v1/marketplace/banners');

        $this->assertNotEquals(301, $response->getStatusCode());
    }

    public function test_post_on_old_domain_is_not_redirected(): void
    {
        $response = $this->post('http://rddshipping.com/enquiries');

        $this->assertNotEquals(301, $response->getStatusCode());
    }

    public function test_current_domain_is_not_redirected(): void
    {
        $response = $this->get('http://kasarose.com/tracking.php?ref=ABC123');

        $this->assertNotEquals('https://kasarose.com/tracking.php?ref=ABC123', $response->headers->get('Location'));
    }
}
