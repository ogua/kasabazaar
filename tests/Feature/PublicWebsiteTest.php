<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\CustomerFeedback;
use App\Models\Product;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Notifications\WebsiteEnquiryReceived;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The public website on kasarose.com (resources/views/site), ported from the plain-PHP
 * KasaRose Website: its pages, the 301s from the old *.php URLs, shipment tracking,
 * news, and the contact / quote / feedback forms.
 */
class PublicWebsiteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'One group moving goods, people and property forward.'],
            'about' => ['/about-us', 'About Us'],
            'our companies' => ['/our-companies', 'KASAROSE MARKET'],
            'services' => ['/our-services', 'Air Freight'],
            'quote' => ['/quote', 'Request a Quote'],
            'tracking' => ['/tracking', 'Enter Your Tracking Number'],
            'contact' => ['/contact-us', 'Contact Us'],
            'feedback' => ['/feedback', 'Customer Feedback'],
            'news' => ['/news', 'News'],
            'projects' => ['/our-projects', 'Our Work'],
            'faq' => ['/faq', 'FAQ'],
            'privacy' => ['/privacy', 'Privacy Policy'],
            'terms' => ['/terms', 'Terms &amp; Conditions'],
            'delivery policy' => ['/delivery-policy', 'Delivery Policy'],
            'sms terms' => ['/sms-terms', 'Reply <strong>STOP</strong>'],
        ];
    }

    #[DataProvider('pages')]
    public function test_every_page_renders(string $path, string $expected): void
    {
        $this->get($path)
            ->assertOk()
            ->assertSee($expected, false)
            ->assertSee(asset('site/css/style.css'), false)
            ->assertDontSee('rddshipping.com');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function appPromoPages(): array
    {
        return [
            'home' => ['/'],
            'services' => ['/our-services'],
            'tracking' => ['/tracking'],
            'contact' => ['/contact-us'],
        ];
    }

    #[DataProvider('appPromoPages')]
    public function test_the_key_pages_link_to_both_app_stores(string $path): void
    {
        config([
            'website.mobile_app.android_url' => 'https://play.google.com/store/apps/details?id=com.kasarose.clientapp',
            'website.mobile_app.ios_url' => 'https://apps.apple.com/us/app/kasarose/id6807400170',
        ]);

        $this->get($path)
            ->assertOk()
            ->assertSee('class="app-promo"', false)
            ->assertSee('https://play.google.com/store/apps/details?id=com.kasarose.clientapp', false)
            ->assertSee('https://apps.apple.com/us/app/kasarose/id6807400170', false)
            ->assertSee('<meta name="apple-itunes-app" content="app-id=6807400170">', false);
    }

    public function test_the_footer_carries_the_app_store_links_on_every_page(): void
    {
        $this->get('/faq')
            ->assertOk()
            ->assertDontSee('class="app-promo"', false)
            ->assertSee(config('website.mobile_app.ios_url'), false)
            ->assertSee(config('website.mobile_app.android_url'), false);
    }

    public function test_hidden_sister_companies_stay_off_the_marketing_pages_but_not_the_legal_ones(): void
    {
        $this->get('/our-companies')->assertOk()->assertDontSee('Neoride Africa');
        $this->get('/')->assertOk()->assertDontSee('Neoride Africa');

        $this->get('/privacy')->assertOk()->assertSee('Neoride Africa');
        $this->get('/terms')->assertOk()->assertSee('Neoride Africa');
    }

    public function test_an_unknown_url_answers_404_with_the_branded_page(): void
    {
        $this->get('/no-such-page-'.Str::random(6))
            ->assertNotFound()
            ->assertSee('that page can', false)
            ->assertSee('noindex, follow', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function legacyUrls(): array
    {
        return [
            'about' => ['/about.php', '/about-us'],
            'services' => ['/services.php', '/our-services'],
            'companies' => ['/our-companies.php', '/our-companies'],
            'contact' => ['/contact.php', '/contact-us'],
            'projects' => ['/projects.php', '/our-projects'],
            'privacy' => ['/privacy-policy.php', '/privacy'],
            'sms terms' => ['/sms-terms.php', '/sms-terms'],
            'tracking with ref' => ['/tracking.php?ref=KBZ123', '/tracking?ref=KBZ123'],
            'article' => ['/news-detail.php?slug=hello-world', '/news/hello-world'],
            'article without slug' => ['/news-detail.php', '/news'],
            'old service page' => ['/ocean-freight.php', '/our-services#ocean-freight'],
        ];
    }

    #[DataProvider('legacyUrls')]
    public function test_the_old_php_urls_redirect_permanently(string $from, string $to): void
    {
        $this->get($from)->assertStatus(301)->assertRedirect(url($to));
    }

    public function test_tracking_by_tracking_number_shows_status_items_and_masked_receivers(): void
    {
        $shipment = $this->makeShipment(['status' => 'in_transit']);
        $this->addItem($shipment, 'Kwame Asante Mensah', 'Barrel (Large)');

        $this->get('/tracking?ref='.$shipment->tracking_number)
            ->assertOk()
            ->assertSee('In Transit')
            ->assertSee('Barrel (Large)')
            ->assertSee('Kwame M.')
            ->assertDontSee('Kwame Asante Mensah')
            ->assertDontSee('+233201234567')
            ->assertSee('id="track-results"', false)
            ->assertSee('Current status')
            ->assertDontSee(' autofocus>', false);
    }

    public function test_tracking_by_shipping_reference_shows_the_status_but_not_the_items(): void
    {
        $shipment = $this->makeShipment(['status' => 'port', 'shipping_reference' => 'CON50-26-C1-'.Str::random(4)]);
        $this->addItem($shipment, 'Kwame Asante Mensah', 'Barrel (Large)');

        $this->get('/tracking?ref='.$shipment->shipping_reference)
            ->assertOk()
            ->assertSee('At Harbour / Port GH')
            ->assertDontSee('Barrel (Large)')
            ->assertSee('search with the tracking', false);
    }

    public function test_tracking_accepts_the_query_parameter_the_shipment_portal_used(): void
    {
        $shipment = $this->makeShipment(['status' => 'shipped']);

        $this->get('/tracking?query='.$shipment->tracking_number)
            ->assertOk()
            ->assertSee($shipment->tracking_number)
            ->assertSee('Shipped');
    }

    public function test_tracking_an_unknown_number_says_so(): void
    {
        $this->get('/tracking?ref=KBZ-NOPE-'.Str::random(6))
            ->assertOk()
            ->assertSee('No shipment found')
            ->assertSee('id="track-results"', false);
    }

    public function test_a_delivered_shipment_with_receipt_confirmed_shows_as_received(): void
    {
        $shipment = $this->makeShipment(['status' => 'delivered', 'is_received' => true]);

        $this->get('/tracking?ref='.$shipment->tracking_number)
            ->assertOk()
            ->assertSee('Received');
    }

    public function test_a_cancelled_shipment_shows_without_the_stage_tracker(): void
    {
        $shipment = $this->makeShipment(['status' => 'cancelled']);

        $this->get('/tracking?ref='.$shipment->tracking_number)
            ->assertOk()
            ->assertSee('Cancelled')
            ->assertDontSee('track-stages', false);
    }

    public function test_news_lists_only_published_posts_and_unpublished_ones_404(): void
    {
        $published = $this->makePost(['status' => 1, 'title' => 'Container 50 has sailed']);
        $draft = $this->makePost(['status' => 0, 'title' => 'Draft about pricing']);

        $this->get('/news')
            ->assertOk()
            ->assertSee('Container 50 has sailed')
            ->assertDontSee('Draft about pricing');

        $this->get('/news/'.$published->slug)
            ->assertOk()
            ->assertSee('Container 50 has sailed')
            ->assertSee('NewsArticle', false);

        $this->get('/news/'.$draft->slug)->assertNotFound();
    }

    public function test_a_news_post_body_is_stripped_of_scripts_and_event_handlers(): void
    {
        $post = $this->makePost([
            'status' => 1,
            'content' => '<p onclick="steal()">Safe text</p><script>alert(1)</script><a href="javascript:alert(2)">x</a>',
        ]);

        $this->get('/news/'.$post->slug)
            ->assertOk()
            ->assertSee('Safe text')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_a_quote_request_is_stored_and_copied_to_the_team(): void
    {
        $email = 'ama-'.Str::random(6).'@example.com';

        $this->post('/enquiries', [
            'form_type' => 'quote',
            'name' => 'Ama Owusu',
            'email' => $email,
            'phone' => '+233509725081',
            'ftype' => 'Ocean Freight',
            'wight' => '120kg',
            'message' => 'Two barrels to Kumasi.',
            'sms_opt_in' => '1',
        ])->assertOk()->assertSee('Thank you');

        $message = ContactMessage::where('email', $email)->firstOrFail();
        $this->assertSame('Quote Request', $message->subject);
        $this->assertTrue((bool) $message->sms_opt_in);
        $this->assertStringContainsString('Service Type: Ocean Freight', $message->message);
        $this->assertStringContainsString('Two barrels to Kumasi.', $message->message);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            WebsiteEnquiryReceived::class,
            fn (WebsiteEnquiryReceived $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === config('website.form_recipients')
                && $notification->formLabel === 'Quote Request'
        );
    }

    public function test_sms_consent_is_optional_and_recorded_as_given(): void
    {
        $email = 'kofi-'.Str::random(6).'@example.com';

        $this->post('/enquiries', [
            'form_type' => 'contact',
            'name' => 'Kofi Boateng',
            'email' => $email,
            'phone' => '+15744407460',
            'message' => 'When does the next container leave?',
        ])->assertOk();

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => $email,
            'subject' => 'Contact Message',
            'sms_opt_in' => false,
        ]);
    }

    public function test_an_invalid_submission_answers_422_in_plain_text(): void
    {
        $this->post('/enquiries', [
            'form_type' => 'contact',
            'name' => 'Kofi Boateng',
            'email' => 'not-an-email',
            'phone' => '+15744407460',
        ])
            ->assertStatus(422)
            ->assertSee('Please enter a valid email address.')
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        Notification::assertNothingSent();
    }

    public function test_an_unknown_form_type_is_rejected(): void
    {
        $this->post('/enquiries', [
            'form_type' => 'newsletter',
            'name' => 'Kofi Boateng',
            'email' => 'kofi@example.com',
            'phone' => '+15744407460',
        ])->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_feedback_is_stored_against_the_matching_shipment(): void
    {
        $shipment = $this->makeShipment(['status' => 'delivered']);
        $email = 'esi-'.Str::random(6).'@example.com';

        $this->post('/enquiries', [
            'form_type' => 'feedback',
            'name' => 'Esi Darko',
            'email' => $email,
            'phone' => '+233545170568',
            'tracking_ref' => $shipment->tracking_number,
            'feedback_about' => 'Shipping & Delivery',
            'feedback_category' => 'Late Delivery',
            'rating' => '4',
            'message' => 'Arrived two days late, but intact.',
        ])->assertOk();

        $feedback = CustomerFeedback::where('customer_email', $email)->firstOrFail();
        $this->assertSame($shipment->id, $feedback->shipment_id);
        $this->assertSame('Late Delivery', $feedback->category);
        $this->assertSame(4, $feedback->rating);
        $this->assertSame('feedback', $feedback->type);
        $this->assertSame('website', $feedback->meta['source']);
    }

    public function test_feedback_requires_a_rating(): void
    {
        $this->post('/enquiries', [
            'form_type' => 'feedback',
            'name' => 'Esi Darko',
            'email' => 'esi@example.com',
            'phone' => '+233545170568',
            'message' => 'Great service.',
        ])->assertStatus(422)->assertSee('Please rate your experience');
    }

    public function test_the_sitemap_lists_the_pages_and_published_posts(): void
    {
        $post = $this->makePost(['status' => 1]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('our-services'), false)
            ->assertSee(route('blog-details', $post->slug), false);
    }

    public function test_invoices_link_to_the_new_tracking_route(): void
    {
        $shipment = $this->makeShipment();

        $this->assertSame(
            rtrim(config('group.parent.url'), '/').'/tracking?ref='.$shipment->tracking_number,
            $shipment->publicTrackingUrl()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeShipment(array $overrides = []): Shipment
    {
        $suffix = Str::random(8);
        $branch = Branch::create([
            'name' => 'Michigan',
            'slug' => 'website-test-'.$suffix,
            'country' => 'USA',
            'state' => 'Michigan',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama-'.$suffix.'@example.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create(array_merge([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $branch->id,
            'status' => 'pickup',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'total' => 500,
        ], $overrides));
    }

    private function addItem(Shipment $shipment, string $receiverName, string $productName): void
    {
        $receiver = Receiver::create([
            'shipment_id' => $shipment->id,
            'receiver_name' => $receiverName,
            'receiver_phone' => '+233201234567',
            'address' => '12 Secret Lane, Kumasi',
        ]);

        ShipmentItem::create([
            'shipment_id' => $shipment->id,
            'receiver_id' => $receiver->id,
            'box_no' => 'B1',
            'product_id' => Product::create(['name' => $productName, 'category' => 'general', 'value' => 120])->id,
            'quantity' => 2,
            'item_cost' => 120,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePost(array $overrides = []): Blog
    {
        $category = BlogCategory::create(['cat' => 'Shipping Updates']);
        $slug = 'post-'.Str::lower(Str::random(10));

        return Blog::create(array_merge([
            'cat_id' => $category->id,
            'slug' => $slug,
            'title' => 'Post '.$slug,
            'description' => 'A short summary.',
            'content' => '<p>Body text.</p>',
            'status' => 1,
            'published_at' => now(),
        ], $overrides));
    }
}
