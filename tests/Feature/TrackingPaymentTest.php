<?php

namespace Tests\Feature;

use App\Enums\ShipmentPaymentAttemptStatus;
use App\Jobs\ProcessPaystackWebhookJob;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Receiver;
use App\Models\Shipment;
use App\Models\ShipmentPaymentAttempt;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\ShipmentAlert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Models\WebhookCall;
use Tests\TestCase;

/**
 * Paying a shipment balance from the public tracking page: the phone/email check that
 * guards the balance, the USD Paystack checkout, the callback, receipts and their
 * verification, and the group-wide Paystack webhook.
 */
class TrackingPaymentTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'sk_test_tracking_payment';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'services.paystack.secret_key' => self::SECRET,
            'services.paystack.payment_url' => 'https://api.paystack.test',
            'services.paystack.shipment_currency' => 'USD',
            'services.paystack.transaction_fee_percent' => 1.99,
            'webhook-client.configs.0.signing_secret' => self::SECRET,
        ]);
    }

    public function test_the_tracking_result_offers_payment_only_when_looked_up_by_tracking_number(): void
    {
        $shipment = $this->makeShipment();

        $this->get('/tracking?ref='.$shipment->tracking_number)
            ->assertOk()
            ->assertSee(route('tracking-payment.create', $shipment->tracking_number), false)
            ->assertDontSee('$500.00');

        $this->get('/tracking?ref='.$shipment->shipping_reference)
            ->assertOk()
            ->assertDontSee(route('tracking-payment.create', $shipment->tracking_number), false);
    }

    public function test_a_cancelled_shipment_cannot_be_paid(): void
    {
        $shipment = $this->makeShipment(['status' => 'cancelled']);

        $this->get('/tracking?ref='.$shipment->tracking_number)
            ->assertDontSee(route('tracking-payment.create', $shipment->tracking_number), false);

        $this->get(route('tracking-payment.create', $shipment->tracking_number))->assertNotFound();
    }

    public function test_the_balance_stays_hidden_until_the_phone_is_confirmed(): void
    {
        $shipment = $this->makeShipment();

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Confirm it&rsquo;s you', false)
            ->assertDontSee('Balance due')
            ->assertDontSee('500.00');
    }

    public function test_a_wrong_phone_is_refused_and_repeated_guesses_are_locked_out(): void
    {
        $shipment = $this->makeShipment();
        $url = route('tracking-payment.confirm', $shipment->tracking_number);

        for ($i = 0; $i < 5; $i++) {
            $this->post($url, ['contact' => '0559999999'])->assertSessionHasErrors('contact');
        }

        $this->post($url, ['contact' => '0201234567'])
            ->assertSessionHasErrors(['contact' => 'Too many attempts. Please try again in 15 minute(s), or call us.']);

        $this->get(route('tracking-payment.create', $shipment->tracking_number))->assertDontSee('Balance due');

        RateLimiter::clear('tracking-payment-contact:'.$shipment->id);
    }

    public function test_the_senders_phone_in_any_format_reveals_the_balance(): void
    {
        $shipment = $this->makeShipment();

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '020 123 4567'])
            ->assertRedirect(route('tracking-payment.create', $shipment->tracking_number));

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertOk()
            ->assertSee('Balance due')
            ->assertSee('$500.00');
    }

    public function test_a_receivers_phone_is_accepted_too(): void
    {
        $shipment = $this->makeShipment();
        Receiver::create(['shipment_id' => $shipment->id, 'receiver_name' => 'Kofi Boateng', 'receiver_phone' => '+233 24 765 4321']);

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '0247654321'])
            ->assertSessionHasNoErrors();
    }

    public function test_the_senders_email_in_any_case_reveals_the_balance(): void
    {
        $shipment = $this->makeShipment();

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '  AMA@Kasarose-Mail.com '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tracking-payment.create', $shipment->tracking_number));

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('Balance due')
            ->assertSee('value="ama@kasarose-mail.com"', false);
    }

    public function test_a_receivers_email_is_accepted_when_the_receiver_has_no_phone(): void
    {
        $shipment = $this->makeShipment();
        Receiver::create(['shipment_id' => $shipment->id, 'receiver_name' => 'Kofi Boateng', 'receiver_email' => 'kofi@kasarose-mail.com']);

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => 'kofi@kasarose-mail.com'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_wrong_email_is_refused_and_counts_towards_the_lockout(): void
    {
        $shipment = $this->makeShipment();
        Receiver::create(['shipment_id' => $shipment->id, 'receiver_name' => 'No Email', 'receiver_email' => null]);
        $url = route('tracking-payment.confirm', $shipment->tracking_number);

        $this->post($url, ['contact' => 'someone@else.com'])
            ->assertSessionHasErrors(['contact' => 'That email doesn’t match our records for this shipment. Use the sender’s or receiver’s email as given when the shipment was booked, or try a phone number.']);

        for ($i = 0; $i < 4; $i++) {
            $this->post($url, ['contact' => '0559999999']);
        }

        $this->post($url, ['contact' => 'ama@kasarose-mail.com'])
            ->assertSessionHasErrors(['contact' => 'Too many attempts. Please try again in 15 minute(s), or call us.']);

        RateLimiter::clear('tracking-payment-contact:'.$shipment->id);
    }

    public function test_something_that_is_neither_a_phone_nor_an_email_is_rejected(): void
    {
        $shipment = $this->makeShipment();

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => 'ama'])
            ->assertSessionHasErrors(['contact' => 'Enter a full phone number (with the area or network code) or an email address.']);

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => ''])
            ->assertSessionHasErrors(['contact' => 'Enter the phone number or email address we have on file for this shipment.']);
    }

    public function test_confirming_with_the_senders_phone_fills_in_the_clients_email_for_the_receipt(): void
    {
        $shipment = $this->makeShipment();
        $this->confirmPhone($shipment);

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('value="ama@kasarose-mail.com"', false);
    }

    public function test_confirming_with_a_receivers_phone_fills_in_that_receivers_email_never_the_senders(): void
    {
        $shipment = $this->makeShipment();
        Receiver::create(['shipment_id' => $shipment->id, 'receiver_name' => 'Kofi Boateng', 'receiver_phone' => '0247654321', 'receiver_email' => 'Kofi@Kasarose-Mail.com']);
        Receiver::create(['shipment_id' => $shipment->id, 'receiver_name' => 'Esi Owusu', 'receiver_phone' => '0271112222']);

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '0247654321']);
        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('value="kofi@kasarose-mail.com"', false)
            ->assertDontSee('ama@kasarose-mail.com');

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '027 111 2222']);
        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('Balance due')
            ->assertDontSee('ama@kasarose-mail.com')
            ->assertDontSee('kofi@kasarose-mail.com');
    }

    public function test_checkout_after_an_email_check_uses_that_persons_phone_for_the_sms_receipt(): void
    {
        $shipment = $this->makeShipment();
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.test/abc', 'access_code' => 'abc', 'reference' => 'ignored'],
        ])]);

        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => 'ama@kasarose-mail.com'])
            ->assertSessionHasNoErrors();

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 100, 'email' => 'ama@kasarose-mail.com'])
            ->assertRedirect('https://checkout.paystack.test/abc');

        $this->assertSame('+233201234567', ShipmentPaymentAttempt::where('shipment_id', $shipment->id)->sole()->payer_phone);
    }

    public function test_a_fully_paid_shipment_says_so_after_the_phone_check(): void
    {
        $shipment = $this->makeShipment();
        Payment::create(['shipment_id' => $shipment->id, 'payment_type' => 'credit', 'currency' => 'USD', 'amount' => 500, 'amount_usd' => 500, 'exchange_rate' => 12]);

        $this->confirmPhone($shipment);

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('This shipment is fully paid')
            ->assertDontSee('Amount to pay');
    }

    public function test_checkout_needs_a_confirmed_phone(): void
    {
        Http::fake();
        $shipment = $this->makeShipment();

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 100, 'email' => 'payer@kasarose-mail.com'])
            ->assertRedirect(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSessionHasErrors('contact');

        Http::assertNothingSent();
    }

    public function test_paying_more_than_the_balance_is_refused(): void
    {
        Http::fake();
        $shipment = $this->makeShipment();
        $this->confirmPhone($shipment);

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 500.01, 'email' => 'payer@kasarose-mail.com'])
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
    }

    public function test_the_transaction_charge_is_grossed_up_so_the_full_amount_reaches_the_shipment(): void
    {
        $service = app(\App\Services\ShipmentPaymentService::class);

        $this->assertSame(['amount' => 100.0, 'fee' => 2.04, 'total' => 102.04, 'percent' => 1.99], $service->chargeFor(100));

        // After Paystack keeps 1.99% of what was charged, at least the full amount remains.
        foreach ([1, 9.99, 150.5, 499.99, 12345.67] as $amount) {
            $charge = $service->chargeFor($amount);
            $this->assertGreaterThanOrEqual($amount, round($charge['total'] * (1 - 0.0199), 2), "Short for {$amount}");
            $this->assertLessThan(0.02, $charge['total'] * (1 - 0.0199) - $amount, "Overcharged for {$amount}");
        }

        config(['services.paystack.transaction_fee_percent' => 0]);
        $this->assertSame(['amount' => 100.0, 'fee' => 0.0, 'total' => 100.0, 'percent' => 0.0], $service->chargeFor(100));
    }

    public function test_the_pay_page_shows_the_transaction_charge_breakdown(): void
    {
        $shipment = $this->makeShipment();
        $this->confirmPhone($shipment);

        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSeeInOrder(['Credited to your shipment', '$500.00', 'Transaction charge (1.99%)', '$10.16', 'Total you pay', '$510.16'])
            ->assertSee('Pay <b id="track-pay-amount">$510.16</b>', false);
    }

    public function test_checkout_charges_the_amount_in_usd_with_our_own_reference(): void
    {
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.test/xyz', 'access_code' => 'xyz'],
        ])]);

        $shipment = $this->makeShipment();
        $this->confirmPhone($shipment);

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 150.5, 'email' => 'payer@kasarose-mail.com'])
            ->assertRedirect('https://checkout.paystack.test/xyz');

        $attempt = ShipmentPaymentAttempt::where('shipment_id', $shipment->id)->sole();
        $this->assertStringStartsWith('KRP-', $attempt->reference);
        $this->assertSame(ShipmentPaymentAttemptStatus::Pending, $attempt->status);
        $this->assertSame('tracking_page', $attempt->source);
        $this->assertSame('payer@kasarose-mail.com', $attempt->payer_email);
        $this->assertEqualsWithDelta(150.50, (float) $attempt->amount_usd, 0.001);
        $this->assertEqualsWithDelta(3.06, (float) $attempt->fee_usd, 0.001);

        // $150.50 credited + $3.06 transaction charge = $153.56, in cents.
        Http::assertSent(fn ($request) => $request['currency'] === 'USD'
            && $request['amount'] === 15356
            && $request['reference'] === $attempt->reference
            && $request['email'] === 'payer@kasarose-mail.com'
            && $request['callback_url'] === route('tracking-payment.callback')
            && $request['metadata']['type'] === 'shipment_payment');
    }

    public function test_a_cedi_checkout_shows_usd_but_charges_ghs_at_todays_rate(): void
    {
        config(['services.paystack.shipment_currency' => 'GHS']);
        $this->recordRate(15.5);

        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.test/ghs', 'access_code' => 'ghs'],
        ])]);

        $shipment = $this->makeShipment(); // snapshot rate 12 — today's 15.5 must win
        $this->confirmPhone($shipment);

        // $100 credited + $2.04 charge = $102.04 → GH₵1,581.62 at 15.5.
        $this->get(route('tracking-payment.create', $shipment->tracking_number))
            ->assertSee('Amount to pay (USD)')
            ->assertSee('$500.00')
            ->assertSee('$1 = GH₵15.50');

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 100, 'email' => 'payer@kasarose-mail.com'])
            ->assertRedirect('https://checkout.paystack.test/ghs');

        Http::assertSent(fn ($request) => $request['currency'] === 'GHS' && $request['amount'] === 158162);

        $attempt = ShipmentPaymentAttempt::where('shipment_id', $shipment->id)->sole();
        $this->assertEqualsWithDelta(100.0, (float) $attempt->amount_usd, 0.001);
        $this->assertEqualsWithDelta(15.5, (float) $attempt->exchange_rate, 0.0001);

        // Paystack confirms the cedi charge; the shipment is credited the USD amount.
        $this->fakeVerify($attempt->reference, 'success', 158162, 'GHS');
        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $payment = Payment::where('payment_ref', $attempt->reference)->sole();
        $this->assertEqualsWithDelta(100.0, (float) $payment->amount_usd, 0.001);
        $this->assertEqualsWithDelta(1581.62, (float) $payment->amount_ghs, 0.001);
        $this->assertEqualsWithDelta(400.0, $shipment->fresh()->outstanding_balance, 0.001);

        $this->get(route('tracking-payment.show', $attempt->reference))
            ->assertSee('Charged in cedis')
            ->assertSee('GHS 1,581.62');
    }

    public function test_with_no_recorded_rate_the_shipments_own_rate_is_used_not_a_default(): void
    {
        \App\Models\ExchangeRateLog::query()->delete();
        $shipment = $this->makeShipment(['exchange_rate_at_shipment' => 13.25]);

        $this->assertSame(13.25, app(\App\Services\ShipmentPaymentService::class)->exchangeRateFor($shipment));
    }

    public function test_a_refused_checkout_is_marked_failed_and_the_payer_told(): void
    {
        Http::fake(['*/transaction/initialize' => Http::response(['status' => false, 'message' => 'Currency not supported by merchant'], 400)]);

        $shipment = $this->makeShipment();
        $this->confirmPhone($shipment);

        $this->post(route('tracking-payment.store', $shipment->tracking_number), ['amount' => 100, 'email' => 'payer@kasarose-mail.com'])
            ->assertSessionHasErrors('amount');

        $attempt = ShipmentPaymentAttempt::where('shipment_id', $shipment->id)->sole();
        $this->assertSame(ShipmentPaymentAttemptStatus::Failed, $attempt->status);
        $this->assertSame('Currency not supported by merchant', $attempt->gateway_response);
    }

    public function test_the_callback_records_the_payment_once_and_sends_email_and_sms_receipts(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200, 'payer@kasarose-mail.com');
        $this->fakeVerify($attempt->reference, 'success', 20000);

        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]))
            ->assertRedirect(route('tracking-payment.show', $attempt->reference));
        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $payment = Payment::where('payment_ref', $attempt->reference)->sole();
        $this->assertEqualsWithDelta(200.0, (float) $payment->amount_usd, 0.001);
        $this->assertSame('USD', $payment->currency);

        $attempt->refresh();
        $this->assertSame(ShipmentPaymentAttemptStatus::Success, $attempt->status);
        $this->assertSame($payment->id, $attempt->payment_id);

        $shipment->refresh();
        $this->assertSame('partial', $shipment->payment_status);
        $this->assertEqualsWithDelta(300.0, $shipment->outstanding_balance, 0.001);

        // The sender gets email + SMS (never WhatsApp for a receipt); the payer gets the email too.
        Notification::assertSentOnDemand(ShipmentAlert::class, function (ShipmentAlert $alert, array $channels, AnonymousNotifiable $notifiable) {
            return $alert->event === 'payment_received'
                && $notifiable->routeNotificationFor('mail') === 'ama@kasarose-mail.com'
                && in_array(SmsChannel::class, $channels, true)
                && in_array('mail', $channels, true);
        });
        Notification::assertSentOnDemand(ShipmentAlert::class, fn (ShipmentAlert $alert, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor('mail') === 'payer@kasarose-mail.com');
        $receipts = Notification::sent(new AnonymousNotifiable, ShipmentAlert::class, fn (ShipmentAlert $alert) => $alert->event === 'payment_received');
        $this->assertCount(2, $receipts, 'One receipt per party, even though the callback ran twice.');
    }

    public function test_a_receipt_is_not_mailed_to_a_reserved_test_domain_but_the_sms_still_goes(): void
    {
        $shipment = $this->makeShipment();
        $shipment->client->update(['email' => 'placeholder@example.com']);
        $attempt = $this->makeAttempt($shipment, 200, 'someone@shop.test');
        $this->fakeVerify($attempt->reference, 'success', 20000);

        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $receipts = Notification::sent(new AnonymousNotifiable, ShipmentAlert::class, fn (ShipmentAlert $alert) => $alert->event === 'payment_received');
        $this->assertCount(1, $receipts);
        Notification::assertSentOnDemand(ShipmentAlert::class, fn (ShipmentAlert $alert, array $channels, AnonymousNotifiable $notifiable) => $alert->event === 'payment_received'
            && $channels === [SmsChannel::class]
            && $notifiable->routeNotificationFor('mail') === null);
    }

    public function test_the_receipt_sms_and_email_carry_the_reference_and_verify_link(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 20000);
        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $payment = Payment::where('payment_ref', $attempt->reference)->sole();
        $alert = new ShipmentAlert($shipment->fresh(), 'payment_received', ['payment' => $payment, 'recipient_name' => 'Ama']);
        $notifiable = (new AnonymousNotifiable)->route('mail', 'ama@kasarose-mail.com')->route('sms', '+233201234567');

        $sms = $alert->toSms($notifiable);
        $this->assertStringContainsString('USD 200.00', $sms);
        $this->assertStringContainsString($attempt->reference, $sms);
        $this->assertStringContainsString(route('tracking-payment.show', $attempt->reference), $sms);

        $mail = $alert->toMail($notifiable);
        $this->assertSame(route('tracking-payment.show', $attempt->reference), $mail->actionUrl);
    }

    public function test_a_payment_with_a_transaction_charge_credits_only_the_amount_and_shows_the_charge(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 100, null, 2.04);
        $this->fakeVerify($attempt->reference, 'success', 10204);

        $this->get(route('tracking-payment.show', $attempt->reference))
            ->assertOk()
            ->assertSeeInOrder(['Payment Details', 'Credited to shipment', '$100.00', 'Transaction charge (1.99%)', '$2.04'])
            ->assertSeeInOrder(['Invoice Total:', '$500.00', 'Paid Before:', '$0.00', 'Total Charged:', '$102.04', 'Balance Remaining:', '$400.00']);

        $payment = Payment::where('payment_ref', $attempt->reference)->sole();
        $this->assertEqualsWithDelta(100.0, (float) $payment->amount_usd, 0.001);
        $this->assertEqualsWithDelta(400.0, $shipment->fresh()->outstanding_balance, 0.001);

        $sms = (new ShipmentAlert($shipment->fresh(), 'payment_received', ['payment' => $payment]))
            ->toSms((new AnonymousNotifiable)->route('sms', '+233201234567'));
        $this->assertStringContainsString('USD 100.00 (plus a USD 2.04 transaction charge)', $sms);
    }

    public function test_a_charge_for_less_than_was_asked_is_not_recorded(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 100);

        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $this->assertSame(0, Payment::where('payment_ref', $attempt->reference)->count());
        $this->assertSame(ShipmentPaymentAttemptStatus::Failed, $attempt->fresh()->status);
    }

    public function test_a_charge_in_the_wrong_currency_is_not_recorded(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 20000, 'GHS');

        $this->get(route('tracking-payment.callback', ['reference' => $attempt->reference]));

        $this->assertSame(0, Payment::where('payment_ref', $attempt->reference)->count());
    }

    public function test_an_unfinished_checkout_stays_pending_on_the_receipt(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'abandoned', 20000);

        $this->get(route('tracking-payment.show', $attempt->reference))
            ->assertOk()
            ->assertSee('Awaiting payment')
            ->assertSee('haven&rsquo;t had confirmation', false);

        $this->assertSame(ShipmentPaymentAttemptStatus::Pending, $attempt->fresh()->status);
    }

    public function test_a_paid_receipt_shows_as_verified_with_the_remaining_balance(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 20000);

        $this->get(route('tracking-payment.show', $attempt->reference))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee($attempt->reference)
            ->assertSee('Verified.')
            ->assertSee('$200.00')
            ->assertSeeInOrder(['Balance Remaining:', '$300.00'])
            ->assertSee($shipment->tracking_number);
    }

    public function test_the_receipt_downloads_as_a_pdf(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 20000);

        $response = $this->get(route('tracking-payment.pdf', $attempt->reference))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('receipt-'.$attempt->reference.'.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_verifying_by_reference(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);

        $this->get(route('tracking-payment.verify'))->assertOk()->assertSee('Enter Your Payment Reference');

        $this->get(route('tracking-payment.verify', ['reference' => strtolower($attempt->reference)]))
            ->assertRedirect(route('tracking-payment.show', $attempt->reference));

        $this->get(route('tracking-payment.verify', ['reference' => 'KRP-NOTAREALONE00']))
            ->assertOk()
            ->assertSee('No payment was found with that reference');

        $this->get(route('tracking-payment.show', 'KRP-NOTAREALONE00'))->assertNotFound();
    }

    public function test_a_signed_webhook_is_stored_and_records_the_payment(): void
    {
        $shipment = $this->makeShipment();
        $attempt = $this->makeAttempt($shipment, 200);
        $this->fakeVerify($attempt->reference, 'success', 20000);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => $attempt->reference, 'metadata' => ['type' => 'shipment_payment', 'shipment_id' => $shipment->id]],
        ]);

        $this->call('POST', '/api/v1/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $payload, self::SECRET),
        ], $payload)->assertOk();

        $this->assertSame(1, WebhookCall::where('name', 'paystack')->where('payload->data->reference', $attempt->reference)->count());
        $this->assertSame(1, Payment::where('payment_ref', $attempt->reference)->count());
        $this->assertSame(ShipmentPaymentAttemptStatus::Success, $attempt->fresh()->status);
    }

    public function test_an_unsigned_or_forged_webhook_is_rejected_and_not_stored(): void
    {
        Http::fake();
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'KRP-FORGED']]);

        $this->call('POST', '/api/v1/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $payload, 'wrong-secret'),
        ], $payload)->assertUnauthorized();

        $this->postJson('/api/v1/webhooks/paystack', ['event' => 'charge.success'])->assertUnauthorized();

        $this->assertSame(0, WebhookCall::where('payload->data->reference', 'KRP-FORGED')->count());
        Http::assertNothingSent();
    }

    public function test_the_reconcile_command_confirms_a_paid_checkout_whose_callback_was_missed(): void
    {
        $shipment = $this->makeShipment();
        $missed = $this->makeAttempt($shipment, 200);
        $missed->forceFill(['created_at' => now()->subMinutes(10)])->save();
        $tooNew = $this->makeAttempt($shipment, 50);
        $this->fakeVerify($missed->reference, 'success', 20000);

        $this->artisan('app:reconcile-shipment-payments')->assertSuccessful();

        $this->assertSame(ShipmentPaymentAttemptStatus::Success, $missed->fresh()->status);
        $this->assertSame(1, Payment::where('payment_ref', $missed->reference)->count());
        $this->assertSame(ShipmentPaymentAttemptStatus::Pending, $tooNew->fresh()->status);
    }

    public function test_webhook_charges_are_routed_by_their_metadata_type(): void
    {
        $this->assertSame('shipment_payment', ProcessPaystackWebhookJob::chargeType(['metadata' => ['type' => 'shipment_payment']]));
        $this->assertSame('investment_deposit', ProcessPaystackWebhookJob::chargeType(['metadata' => ['type' => 'investment_deposit']]));
        $this->assertSame('ecommerce_order_group', ProcessPaystackWebhookJob::chargeType(['metadata' => '{"type":"ecommerce_order_group"}']));
        // Older mobile-app checkouts carried a shipment id but no type.
        $this->assertSame('shipment_payment', ProcessPaystackWebhookJob::chargeType(['metadata' => ['shipment_id' => 'abc']]));
        $this->assertNull(ProcessPaystackWebhookJob::chargeType(['metadata' => []]));
    }

    private function makeShipment(array $overrides = []): Shipment
    {
        $suffix = Str::random(8);
        $branch = Branch::create([
            'name' => 'Michigan',
            'slug' => 'pay-test-'.$suffix,
            'country' => 'USA',
            'state' => 'Michigan',
            'address' => '1 Test Street',
            'email' => 'branch-'.$suffix.'@example.com',
            'phone' => '0200000000',
        ]);

        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Ama Mensah',
            'email' => 'ama@kasarose-mail.com',
            'phone' => '+233201234567',
        ]);

        return Shipment::create(array_merge([
            'client_id' => $client->id,
            'branch_id' => $branch->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $branch->id,
            'status' => 'warehouse',
            'tracking_number' => Shipment::generateTrackingNumber(),
            'shipping_reference' => 'CON9-26-C1-'.random_int(100, 999),
            'exchange_rate_at_shipment' => 12,
            'total' => 500,
        ], $overrides));
    }

    private function makeAttempt(Shipment $shipment, float $amountUsd, ?string $payerEmail = null, float $feeUsd = 0): ShipmentPaymentAttempt
    {
        return ShipmentPaymentAttempt::create([
            'shipment_id' => $shipment->id,
            'reference' => ShipmentPaymentAttempt::generateReference(),
            'source' => 'tracking_page',
            'amount_usd' => $amountUsd,
            'fee_percent' => $feeUsd > 0 ? 1.99 : 0,
            'fee_usd' => $feeUsd,
            'currency' => 'USD',
            'amount_subunit' => (int) round(($amountUsd + $feeUsd) * 100),
            'payer_email' => $payerEmail,
            'payer_phone' => '0201234567',
            'status' => ShipmentPaymentAttemptStatus::Pending,
        ]);
    }

    private function fakeVerify(string $reference, string $status, int $amount, string $currency = 'USD'): void
    {
        Http::fake(['*/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => $status,
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'channel' => 'card',
                'gateway_response' => $status === 'success' ? 'Approved' : 'The transaction was not completed',
                'paid_at' => now()->toIso8601String(),
                'metadata' => ['type' => 'shipment_payment'],
            ],
        ])]);
    }

    private function recordRate(float $rate): void
    {
        \App\Models\ExchangeRateLog::query()->delete();
        \App\Models\ExchangeRateLog::create(['from_currency' => 'USD', 'to_currency' => 'GHS', 'rate' => $rate, 'rate_date' => today(), 'source' => 'manual']);
        \Illuminate\Support\Facades\Cache::forget('exchange_rate_USD_GHS');
    }

    private function confirmPhone(Shipment $shipment): void
    {
        $this->post(route('tracking-payment.confirm', $shipment->tracking_number), ['contact' => '+233201234567'])
            ->assertSessionHasNoErrors();
    }
}
