<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\EcommerceOrder;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsappChannel;
use App\Notifications\Ecommerce\EcommerceOrderDispatched;
use App\Notifications\Ecommerce\VendorNewOrderReceived;
use App\Notifications\ShipmentAlert;
use App\Service\SystemSetting;
use App\Services\ShipmentNotifier;
use App\Services\Whatsapp\PhoneNumber;
use App\Services\Whatsapp\WhatsappSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private const CLIENT_SECRET = 'psk_live_secret';

    private const CALLBACK_SECRET = 'callback-secret';

    protected function setUp(): void
    {
        parent::setUp();

        $settings = [
            'whatsapp_enabled' => 1,
            'whatsapp_gateway_url' => 'https://gateway.test',
            'whatsapp_client_key' => 'pck_live_key',
            'whatsapp_client_secret' => self::CLIENT_SECRET,
            'whatsapp_callback_secret' => self::CALLBACK_SECRET,
            'whatsapp_test_numbers' => '',
            'arkesel_key' => 'arkesel-key',
            WhatsappSettings::templateSettingKey('logistics', 'status') => 'shipment_status_update|en',
            WhatsappSettings::templateSettingKey('marketplace', 'order_dispatched') => 'order_dispatched|en',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value, 'whatsapp');
        }
    }

    private function makeShipment(array $clientOverrides = []): Shipment
    {
        Notification::fake();

        $client = Client::create(array_merge([
            'branch_id' => (string) Str::uuid(),
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '0241234567',
        ], $clientOverrides));

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

    private function signedCallback(array $payload, string $secret = self::CALLBACK_SECRET, ?int $timestamp = null)
    {
        $raw = json_encode($payload);
        $timestamp ??= time();

        return $this->call('POST', '/api/v1/webhooks/whatsapp-gateway', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$raw, $secret),
        ], $raw);
    }

    // ── Routing: email always, WhatsApp or SMS for the phone ────────────────

    public function test_an_opted_in_client_gets_whatsapp_and_email_but_not_sms(): void
    {
        $shipment = $this->makeShipment(['whatsapp_opt_in_at' => now()]);

        ShipmentNotifier::sent($shipment, 'status:shipped');

        Notification::assertSentOnDemand(ShipmentAlert::class, function (ShipmentAlert $notification, array $channels, AnonymousNotifiable $notifiable) {
            return $channels === ['mail', WhatsappChannel::class]
                && $notifiable->routes['whatsapp'] === '0241234567'
                && $notifiable->routes['mail'] === 'ama@example.com'
                && ! isset($notifiable->routes['sms']);
        });
    }

    public function test_a_client_who_has_not_opted_in_keeps_getting_sms_and_email(): void
    {
        $shipment = $this->makeShipment();

        ShipmentNotifier::sent($shipment, 'status:shipped');

        Notification::assertSentOnDemand(ShipmentAlert::class, fn ($n, array $channels) => $channels === ['mail', SmsChannel::class]);
    }

    public function test_an_opt_out_overrides_an_earlier_opt_in(): void
    {
        $shipment = $this->makeShipment(['whatsapp_opt_in_at' => now()->subWeek(), 'whatsapp_opted_out_at' => now()]);

        ShipmentNotifier::sent($shipment, 'status:shipped');

        Notification::assertSentOnDemand(ShipmentAlert::class, fn ($n, array $channels) => $channels === ['mail', SmsChannel::class]);
    }

    public function test_events_without_a_mapped_template_stay_on_sms(): void
    {
        $shipment = $this->makeShipment(['whatsapp_opt_in_at' => now()]);

        ShipmentNotifier::sent($shipment, 'created');

        Notification::assertSentOnDemand(ShipmentAlert::class, fn ($n, array $channels) => $channels === ['mail', SmsChannel::class]);
    }

    public function test_the_rollout_allowlist_limits_whatsapp_to_listed_numbers(): void
    {
        SystemSetting::set('whatsapp_test_numbers', '+233200000001', 'whatsapp');
        $shipment = $this->makeShipment(['whatsapp_opt_in_at' => now()]);

        ShipmentNotifier::sent($shipment, 'status:shipped');

        Notification::assertSentOnDemand(ShipmentAlert::class, fn ($n, array $channels) => $channels === ['mail', SmsChannel::class]);
    }

    public function test_an_attested_receiver_gets_whatsapp_on_dispatch(): void
    {
        $shipment = $this->makeShipment();
        Receiver::create([
            'shipment_id' => $shipment->id,
            'receiver_name' => 'Kofi',
            'receiver_phone' => '+233209999999',
            'whatsapp_opt_in_at' => now(),
        ]);

        ShipmentNotifier::sent($shipment, 'status:shipped');

        Notification::assertSentOnDemand(ShipmentAlert::class, fn ($n, array $channels, AnonymousNotifiable $notifiable) => ($notifiable->routes['whatsapp'] ?? null) === '+233209999999'
            && $channels === [WhatsappChannel::class]);

        $this->assertSame('client_attested', Receiver::where('receiver_phone', '+233209999999')->value('whatsapp_opt_in_source'));
    }

    // ── WhatsappChannel ─────────────────────────────────────────────────────

    public function test_the_channel_sends_a_signed_template_request_to_the_gateway(): void
    {
        $shipment = $this->makeShipment();
        Http::fake(['gateway.test/*' => Http::response(['status' => 'success', 'data' => ['id' => 'MSG-1', 'status' => 'queued']], 202)]);

        $notification = new ShipmentAlert($shipment, 'status:shipped', ['recipient_name' => 'Ama']);
        $notification->id = 'notif-1';

        app(WhatsappChannel::class)->send((new AnonymousNotifiable)->route('whatsapp', '0241234567'), $notification);

        Http::assertSent(function (Request $request) {
            $expected = hash_hmac('sha256', $request->header('X-Timestamp')[0].'.POST.api/partner/v1/whatsapp/messages.'.$request->body(), self::CLIENT_SECRET);

            return $request->url() === 'https://gateway.test/api/partner/v1/whatsapp/messages'
                && $request->header('X-Signature')[0] === $expected
                && $request['sender'] === 'logistics'
                && $request['to'] === '+233241234567'
                && $request['template'] === 'shipment_status_update'
                && $request['body_params'] === ['Ama', 'SHP-001', 'in transit', 'USD 500.00']
                && $request['button_url_suffix'] === 'tok123'
                && $request['idempotency_key'] === 'notif-1:+233241234567';
        });

        $message = WhatsappMessage::sole();
        $this->assertSame('MSG-1', $message->gateway_message_id);
        $this->assertSame(WhatsappMessage::STATUS_QUEUED, $message->status);
        $this->assertNull($message->fallback_sent_at);
    }

    public function test_a_gateway_error_falls_back_to_sms_once(): void
    {
        $shipment = $this->makeShipment();
        Http::fake([
            'gateway.test/*' => Http::response(['status' => 'error', 'message' => 'Sender not connected.'], 409),
            'sms.oguaschoolz.com/*' => Http::response(['status' => 'success']),
        ]);

        $notification = new ShipmentAlert($shipment, 'status:shipped', ['recipient_name' => 'Ama']);
        $notification->id = 'notif-2';

        app(WhatsappChannel::class)->send((new AnonymousNotifiable)->route('whatsapp', '0241234567'), $notification);

        $message = WhatsappMessage::sole();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame('409', $message->error_code);
        $this->assertNotNull($message->fallback_sent_at);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'sms.oguaschoolz.com')
            && $request['recipients'] === ['233241234567']
            && str_contains($request['message'], 'SHP-001'));

        $message->sendFallbackSms();
        Http::assertSentCount(2);
    }

    // ── Gateway callbacks ───────────────────────────────────────────────────

    public function test_callbacks_with_a_bad_signature_or_stale_timestamp_are_rejected(): void
    {
        $this->signedCallback(['event' => 'ping', 'data' => []], 'wrong-secret')->assertUnauthorized();
        $this->signedCallback(['event' => 'ping', 'data' => []], self::CALLBACK_SECRET, time() - 900)->assertUnauthorized();
        $this->signedCallback(['event' => 'ping', 'data' => []])->assertOk();
    }

    public function test_a_failed_status_callback_sends_the_sms_fallback_exactly_once(): void
    {
        Http::fake(['sms.oguaschoolz.com/*' => Http::response(['status' => 'success'])]);
        $message = WhatsappMessage::factory()->create(['gateway_message_id' => 'MSG-9']);

        $payload = ['event' => 'message.status', 'data' => ['id' => 'MSG-9', 'status' => 'failed', 'error_code' => '131026', 'error_message' => 'Message undeliverable']];

        $this->signedCallback($payload)->assertOk();
        $this->signedCallback($payload)->assertOk();

        $message->refresh();
        $this->assertSame(WhatsappMessage::STATUS_FAILED, $message->status);
        $this->assertSame('131026', $message->error_code);
        $this->assertNotNull($message->fallback_sent_at);
        Http::assertSentCount(1);
    }

    public function test_status_callbacks_only_move_forward(): void
    {
        $message = WhatsappMessage::factory()->create(['gateway_message_id' => 'MSG-7', 'status' => WhatsappMessage::STATUS_DELIVERED]);

        $this->signedCallback(['event' => 'message.status', 'data' => ['id' => 'MSG-7', 'status' => 'sent']])->assertOk();
        $this->assertSame(WhatsappMessage::STATUS_DELIVERED, $message->fresh()->status);

        $this->signedCallback(['event' => 'message.status', 'data' => ['id' => 'MSG-7', 'status' => 'read']])->assertOk();
        $this->assertSame(WhatsappMessage::STATUS_READ, $message->fresh()->status);
    }

    public function test_a_stop_reply_opts_the_number_out_everywhere_it_is_stored(): void
    {
        $shipment = $this->makeShipment(['whatsapp_opt_in_at' => now()]);
        $receiver = Receiver::create([
            'shipment_id' => $shipment->id,
            'receiver_name' => 'Ama',
            'receiver_phone' => '+233241234567',
            'whatsapp_opt_in_at' => now(),
        ]);
        $other = Client::create(['branch_id' => $shipment->branch_id, 'name' => 'Other', 'email' => 'other@example.com', 'phone' => '0200000000', 'whatsapp_opt_in_at' => now()]);

        $this->signedCallback(['event' => 'contact.opted_out', 'data' => ['phone' => '233241234567']])->assertOk();

        $this->assertFalse($shipment->client->fresh()->hasWhatsappConsent());
        $this->assertFalse($receiver->fresh()->hasWhatsappConsent());
        $this->assertTrue($other->fresh()->hasWhatsappConsent());

        $this->signedCallback(['event' => 'contact.opted_in', 'data' => ['phone' => '233241234567']])->assertOk();
        $this->assertTrue($shipment->client->fresh()->hasWhatsappConsent());
    }

    // ── Marketplace notifications ───────────────────────────────────────────

    public function test_marketplace_notifications_use_whatsapp_only_for_opted_in_users_and_keep_email(): void
    {
        $order = new EcommerceOrder;
        $order->order_number = 'ORD-100';
        $order->total_ghs = 250;

        $optedIn = new User(['name' => 'Esi', 'phone' => '0245556666']);
        $optedIn->whatsapp_opt_in_at = now();
        $notOptedIn = new User(['name' => 'Yaw', 'phone' => '0245557777']);

        $this->assertSame(['database', 'mail', WhatsappChannel::class], (new EcommerceOrderDispatched($order))->via($optedIn));
        $this->assertSame(['database', 'mail', SmsChannel::class], (new EcommerceOrderDispatched($order))->via($notOptedIn));

        // The vendor event has no template mapped in this test, so it stays on SMS even for an opted-in vendor.
        $this->assertSame(['database', 'mail', SmsChannel::class], (new VendorNewOrderReceived($order))->via($optedIn));

        $message = (new EcommerceOrderDispatched($order))->toWhatsapp($optedIn);
        $this->assertSame('marketplace', $message->sender);
        $this->assertSame(['Esi', 'ORD-100', 'Pending'], $message->bodyParams());
        $this->assertSame('ORD-100', $message->buttonUrlSuffix);
    }

    // ── Phone numbers ───────────────────────────────────────────────────────

    public function test_phone_numbers_normalise_to_e164(): void
    {
        $this->assertSame('+233241234567', PhoneNumber::toE164('0241234567'));
        $this->assertSame('+233241234567', PhoneNumber::toE164('241234567'));
        $this->assertSame('+233241234567', PhoneNumber::toE164('+233 24 123 4567'));
        $this->assertSame('+447700900123', PhoneNumber::toE164('00447700900123'));
        $this->assertNull(PhoneNumber::toE164('12345'));
        $this->assertNull(PhoneNumber::toE164(null));

        $this->assertSame(
            ['+233241234567', '233241234567', '0241234567', '241234567', '+233 241234567'],
            PhoneNumber::storedVariants('233241234567')
        );
    }
}
