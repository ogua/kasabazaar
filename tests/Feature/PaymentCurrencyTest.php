<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaymentCurrencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usd_entry_derives_ghs_equivalent(): void
    {
        $payment = Payment::create([
            'currency' => 'USD',
            'amount_usd' => 100,
            'exchange_rate' => 12,
            'payment_type' => 'credit',
        ]);

        $this->assertEquals(1200.00, (float) $payment->amount_ghs);
        $this->assertEquals(100.00, (float) $payment->amount);
    }

    public function test_ghs_entry_derives_usd_equivalent(): void
    {
        $payment = Payment::create([
            'currency' => 'GHS',
            'amount_ghs' => 1200,
            'exchange_rate' => 12,
            'payment_type' => 'credit',
        ]);

        $this->assertEquals(100.00, (float) $payment->amount_usd);
        $this->assertEquals(100.00, (float) $payment->amount, 'Legacy amount column must mirror USD, not GHS.');
    }

    public function test_legacy_amount_only_creation_still_backfills_usd_and_ghs(): void
    {
        // Mirrors ShipmentResource's inline "record payment" action, which only sets `amount`.
        $payment = Payment::create(['amount' => 50, 'payment_type' => 'credit']);

        $this->assertEquals(50.00, (float) $payment->amount_usd);
        $this->assertNotNull($payment->exchange_rate);
        $this->assertGreaterThan(0, (float) $payment->amount_ghs);
    }

    public function test_webhook_provided_ghs_amount_is_not_overwritten_on_create(): void
    {
        // Mirrors PaystackWebhookController, which computes both amounts itself
        // without an exchange_rate — the model must not clobber its figures.
        $payment = Payment::create([
            'amount_usd' => 100,
            'amount_ghs' => 1150, // deliberately not amount_usd * rate
            'payment_type' => 'credit',
        ]);

        $this->assertEquals(1150.00, (float) $payment->amount_ghs);
        $this->assertNotNull($payment->exchange_rate);
    }

    public function test_editing_usd_amount_recalculates_ghs(): void
    {
        $payment = Payment::create([
            'currency' => 'USD',
            'amount_usd' => 100,
            'exchange_rate' => 12,
            'payment_type' => 'credit',
        ]);

        $payment->update(['amount_usd' => 200]);

        $this->assertEquals(2400.00, (float) $payment->fresh()->amount_ghs);
    }

    public function test_editing_ghs_amount_recalculates_usd(): void
    {
        $payment = Payment::create([
            'currency' => 'GHS',
            'amount_ghs' => 1200,
            'exchange_rate' => 12,
            'payment_type' => 'credit',
        ]);

        $payment->update(['amount_ghs' => 2400]);

        $this->assertEquals(200.00, (float) $payment->fresh()->amount_usd);
    }
}
