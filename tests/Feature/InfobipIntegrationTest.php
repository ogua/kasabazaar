<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsappSettings as WhatsappSettingsPage;
use App\Models\Client;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Notifications\Channels\WhatsappChannel;
use App\Notifications\ShipmentAlert;
use App\Service\NotificationService;
use App\Service\SystemSetting;
use App\Services\Whatsapp\InfobipWhatsappClient;
use App\Services\Whatsapp\WhatsappGateway;
use App\Services\Whatsapp\WhatsappSettings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfobipIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private const SENDER_NUMBER = '447860088970';

    protected function setUp(): void
    {
        parent::setUp();

        $settings = [
            'whatsapp_enabled' => 1,
            'whatsapp_test_numbers' => '',
            'infobip_base_url' => 'https://infobip.test',
            'infobip_whatsapp_key' => 'wa-key',
            'infobip_whatsapp_number_logistics' => '+'.self::SENDER_NUMBER,
            'infobip_sms_key' => 'sms-key',
            'infobip_sms_sender' => 'KASAROSE',
            'arkesel_key' => 'arkesel-key',
            WhatsappSettings::templateSettingKey('logistics', 'status') => 'shipment_status_update|en',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value, 'whatsapp');
        }
    }

    private function makeShipment(): Shipment
    {
        Notification::fake();

        $client = Client::create([
            'branch_id' => (string) Str::uuid(),
            'name' => 'Ama Mensah',
            'email' => 'ama@kasarose-mail.com',
            'phone' => '0241234567',
            'whatsapp_opt_in_at' => now(),
        ]);

        $shipment = Shipment::create([
            'client_id' => $client->id,
            'branch_id' => $client->branch_id,
            'origin_branch_id' => 'US-WAREHOUSE',
            'destination_branch_id' => 'ACCRA',
            'status' => 'pending',
            'shipping_reference' => 'SHP-001',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'exchange_rate_at_shipment' => 12,
            'total' => 500,
        ]);

        $shipment->forceFill(['public_view_token' => 'tok123'])->saveQuietly();

        return $shipment;
    }

    /**
     * @return array<string, mixed>
     */
    private function templatesResponse(): array
    {
        return ['templates' => [
            [
                'name' => 'shipment_status_update',
                'language' => 'en',
                'status' => 'APPROVED',
                'structure' => [
                    'body' => ['text' => 'Hi {{1}}, shipment {{2}} is now {{3}}. Balance due: {{4}}.'],
                    'buttons' => [['type' => 'URL', 'text' => 'Track', 'url' => 'https://kasarose.com/my-shipment/{{1}}']],
                ],
            ],
            [
                'name' => 'plain_status',
                'language' => 'en',
                'status' => 'APPROVED',
                'structure' => ['body' => ['text' => 'Hi {{1}}, {{2}} is {{3}}. Due {{4}}. Thanks {{1}}!']],
            ],
            [
                'name' => 'short_one',
                'language' => 'en',
                'status' => 'APPROVED',
                'structure' => ['body' => ['text' => 'Hi {{1}}, {{2}}']],
            ],
            [
                'name' => 'rejected_one',
                'language' => 'en',
                'status' => 'REJECTED',
                'structure' => ['body' => ['text' => '{{1}} {{2}} {{3}} {{4}}']],
            ],
        ]];
    }

    private function sendStatusAlert(string $notificationId): void
    {
        $notification = new ShipmentAlert($this->makeShipment(), 'status:shipped', ['recipient_name' => 'Ama']);
        $notification->id = $notificationId;

        app(WhatsappChannel::class)->send((new AnonymousNotifiable)->route('whatsapp', '0241234567'), $notification);
    }

    private function webhook(array $results, ?string $token = null)
    {
        return $this->postJson('/api/v1/webhooks/infobip/'.($token ?? WhatsappSettings::infobipWebhookToken()), ['results' => $results]);
    }

    // ── Defaults ────────────────────────────────────────────────────────────

    public function test_infobip_is_the_default_whatsapp_provider(): void
    {
        $this->assertSame(WhatsappSettings::PROVIDER_INFOBIP, WhatsappSettings::provider());
        $this->assertInstanceOf(InfobipWhatsappClient::class, app(WhatsappGateway::class));
        $this->assertTrue(WhatsappSettings::enabled());
        $this->assertSame(self::SENDER_NUMBER, WhatsappSettings::infobipNumber('marketplace'));
    }

    public function test_whatsapp_is_not_enabled_without_an_infobip_key(): void
    {
        SystemSetting::set('infobip_whatsapp_key', null, 'whatsapp');
        config(['services.infobip.whatsapp_key' => null]);

        $this->assertFalse(WhatsappSettings::enabled());
    }

    // ── SMS ─────────────────────────────────────────────────────────────────

    public function test_international_sms_goes_through_infobip(): void
    {
        Http::fake(['infobip.test/*' => Http::response(['messages' => [['messageId' => 'SMS-1']]])]);

        NotificationService::sendSmsToSender('+1 (415) 555-0123', 'Your shipment SHP-001 has shipped.');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://infobip.test/sms/2/text/advanced'
            && $request->header('Authorization')[0] === 'App sms-key'
            && $request['messages'] === [[
                'from' => 'KASAROSE',
                'destinations' => [['to' => '14155550123']],
                'text' => 'Your shipment SHP-001 has shipped.',
            ]]);
    }

    public function test_ghana_sms_stays_on_arkesel(): void
    {
        Http::fake(['sms.oguaschoolz.com/*' => Http::response(['status' => 'success'])]);

        NotificationService::sendSmsToSender('0241234567', 'Hello');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'sms.oguaschoolz.com'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'infobip.test'));
    }

    public function test_international_sms_falls_back_to_twilio_without_an_infobip_key(): void
    {
        SystemSetting::set('infobip_sms_key', null, 'sms');
        config(['services.infobip.sms_key' => null]);
        SystemSetting::set('twilio_sid', 'AC123', 'sms');
        SystemSetting::set('twilio_token', 'token', 'sms');
        SystemSetting::set('twilio_from', '+12025550000', 'sms');
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        NotificationService::sendSmsToSender('+14155550123', 'Hello');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.twilio.com/2010-04-01/Accounts/AC123/Messages.json')
            && $request['To'] === '+14155550123');
    }

    // ── WhatsApp sends ──────────────────────────────────────────────────────

    public function test_the_channel_sends_an_infobip_template_message(): void
    {
        Http::fake([
            'infobip.test/whatsapp/2/senders/'.self::SENDER_NUMBER.'/templates' => Http::response($this->templatesResponse()),
            'infobip.test/whatsapp/1/message/template' => Http::response(['bulkId' => 'B1', 'messages' => [[
                'to' => '233241234567',
                'messageId' => 'IB-1',
                'status' => ['groupId' => 1, 'groupName' => 'PENDING', 'id' => 7, 'name' => 'PENDING_ENROUTE'],
            ]]]),
        ]);

        $this->sendStatusAlert('notif-ib-1');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://infobip.test/whatsapp/1/message/template'
            && $request->header('Authorization')[0] === 'App wa-key'
            && $request['messages'][0] === [
                'from' => self::SENDER_NUMBER,
                'to' => '233241234567',
                'content' => [
                    'templateName' => 'shipment_status_update',
                    'templateData' => [
                        'body' => ['placeholders' => ['Ama', 'SHP-001', 'shipped', 'USD 500.00']],
                        'buttons' => [['type' => 'URL', 'parameter' => 'tok123']],
                    ],
                    'language' => 'en',
                ],
                'callbackData' => 'SHP-001',
                'notifyUrl' => WhatsappSettings::infobipWebhookUrl(),
            ]);

        $message = WhatsappMessage::sole();
        $this->assertSame('IB-1', $message->gateway_message_id);
        $this->assertSame(WhatsappMessage::STATUS_QUEUED, $message->status);
        $this->assertNull($message->fallback_sent_at);
    }

    public function test_no_button_parameter_is_sent_for_a_template_without_a_url_button(): void
    {
        SystemSetting::set(WhatsappSettings::templateSettingKey('logistics', 'status'), 'plain_status|en', 'whatsapp');
        Http::fake([
            'infobip.test/whatsapp/2/senders/*' => Http::response($this->templatesResponse()),
            'infobip.test/whatsapp/1/message/template' => Http::response(['messages' => [['messageId' => 'IB-2', 'status' => ['groupName' => 'PENDING']]]]),
        ]);

        $this->sendStatusAlert('notif-ib-2');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://infobip.test/whatsapp/1/message/template'
            && ! isset($request['messages'][0]['content']['templateData']['buttons']));
    }

    public function test_an_infobip_error_falls_back_to_sms(): void
    {
        Http::fake([
            'infobip.test/whatsapp/2/senders/*' => Http::response($this->templatesResponse()),
            'infobip.test/whatsapp/1/message/template' => Http::response(['requestError' => ['serviceException' => ['messageId' => 'BAD_REQUEST', 'text' => 'Bad request']]], 400),
            'sms.oguaschoolz.com/*' => Http::response(['status' => 'success']),
        ]);

        $this->sendStatusAlert('notif-ib-3');

        $message = WhatsappMessage::sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame('400', $message->error_code);
        $this->assertSame('Bad request', $message->error_message);
        $this->assertNotNull($message->fallback_sent_at);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'sms.oguaschoolz.com') && $request['recipients'] === ['233241234567']);
    }

    public function test_a_rejected_send_response_falls_back_to_sms(): void
    {
        Http::fake([
            'infobip.test/whatsapp/2/senders/*' => Http::response($this->templatesResponse()),
            'infobip.test/whatsapp/1/message/template' => Http::response(['messages' => [['messageId' => 'IB-4', 'status' => ['groupName' => 'REJECTED', 'description' => 'Destination not registered']]]]),
            'sms.oguaschoolz.com/*' => Http::response(['status' => 'success']),
        ]);

        $this->sendStatusAlert('notif-ib-4');

        $message = WhatsappMessage::sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame('Destination not registered', $message->error_message);
        $this->assertNotNull($message->fallback_sent_at);
    }

    // ── Webhook ─────────────────────────────────────────────────────────────

    public function test_the_webhook_rejects_a_wrong_token(): void
    {
        $this->webhook([], 'wrong-token')->assertUnauthorized();
        $this->webhook([])->assertOk();
    }

    public function test_a_failed_delivery_report_sends_the_sms_fallback_exactly_once(): void
    {
        Http::fake(['sms.oguaschoolz.com/*' => Http::response(['status' => 'success'])]);
        $message = WhatsappMessage::factory()->create(['gateway_message_id' => 'IB-9']);

        $report = [
            'messageId' => 'IB-9',
            'status' => ['groupId' => 2, 'groupName' => 'UNDELIVERABLE', 'id' => 9, 'name' => 'UNDELIVERABLE_NOT_DELIVERED'],
            'error' => ['groupId' => 1, 'groupName' => 'HANDSET_ERRORS', 'id' => 7012, 'name' => 'EC_UNREGISTERED', 'description' => 'User is not on WhatsApp'],
        ];

        $this->webhook([$report])->assertOk();
        $this->webhook([$report])->assertOk();

        $message->refresh();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame('7012', $message->error_code);
        $this->assertSame('User is not on WhatsApp', $message->error_message);
        $this->assertNotNull($message->fallback_sent_at);
        Http::assertSentCount(1);
    }

    public function test_delivered_and_seen_reports_move_the_status_forward_only(): void
    {
        $message = WhatsappMessage::factory()->create(['gateway_message_id' => 'IB-7']);

        $this->webhook([['messageId' => 'IB-7', 'status' => ['groupName' => 'DELIVERED'], 'error' => ['groupName' => 'OK', 'id' => 0]]])->assertOk();
        $this->assertSame(WhatsappMessage::STATUS_DELIVERED, $message->fresh()->status);

        $this->webhook([['messageId' => 'IB-7', 'status' => ['groupName' => 'PENDING']]])->assertOk();
        $this->assertSame(WhatsappMessage::STATUS_DELIVERED, $message->fresh()->status);

        $this->webhook([['messageId' => 'IB-7', 'from' => self::SENDER_NUMBER, 'to' => '233241234567', 'seenAt' => '2026-10-27T10:00:00.000+0000']])->assertOk();
        $this->assertSame(WhatsappMessage::STATUS_READ, $message->fresh()->status);
    }

    public function test_an_inbound_stop_reply_opts_the_number_out_and_start_opts_it_back_in(): void
    {
        $shipment = $this->makeShipment();
        $receiver = Receiver::create([
            'shipment_id' => $shipment->id,
            'receiver_name' => 'Ama',
            'receiver_phone' => '+233241234567',
            'whatsapp_opt_in_at' => now(),
        ]);

        $inbound = fn (string $text): array => [
            'from' => '233241234567',
            'to' => self::SENDER_NUMBER,
            'integrationType' => 'WHATSAPP',
            'messageId' => (string) Str::uuid(),
            'message' => ['type' => 'TEXT', 'text' => $text],
        ];

        $this->webhook([$inbound('hello')])->assertOk();
        $this->assertTrue($shipment->client->fresh()->hasWhatsappConsent());

        $this->webhook([$inbound(' stop ')])->assertOk();
        $this->assertFalse($shipment->client->fresh()->hasWhatsappConsent());
        $this->assertFalse($receiver->fresh()->hasWhatsappConsent());

        $this->webhook([$inbound('START')])->assertOk();
        $this->assertTrue($shipment->client->fresh()->hasWhatsappConsent());
    }

    // ── Settings page ───────────────────────────────────────────────────────

    public function test_the_settings_page_lists_approved_infobip_templates_by_parameter_count(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Http::fake(['infobip.test/whatsapp/2/senders/'.self::SENDER_NUMBER.'/templates' => Http::response($this->templatesResponse())]);

        $statusKey = WhatsappSettings::templateSettingKey('logistics', 'status');

        $page = Livewire::test(WhatsappSettingsPage::class)
            ->assertOk()
            ->assertSet('data.whatsapp_provider', WhatsappSettings::PROVIDER_INFOBIP)
            ->assertSet('gatewayError', null);

        $this->assertSame(
            ['shipment_status_update|en' => 'shipment_status_update (en)', 'plain_status|en' => 'plain_status (en)'],
            $page->instance()->form->getComponent("data.{$statusKey}")->getOptions()
        );

        $page->set('data.infobip_whatsapp_number_marketplace', '+233 20 000 0001')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('233200000001', WhatsappSettings::infobipNumber('marketplace'));
        $this->assertSame(['template' => 'shipment_status_update', 'language' => 'en'], WhatsappSettings::templateFor('logistics', 'status'));
    }
}
