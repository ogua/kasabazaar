<?php

namespace App\Services;

use App\Enums\ShipmentPaymentAttemptStatus;
use App\Models\ExchangeRateLog;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentPaymentAttempt;
use App\Service\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The one Paystack integration for shipment payments: start a checkout for any
 * amount up to the outstanding balance, and verify + record the result.
 *
 * Shipment balances are USD-denominated (`payments.amount` is always USD). They are
 * charged in `services.paystack.shipment_currency` — USD, or GHS converted at
 * today's USD→GHS rate for a Paystack account without USD enabled (the amount
 * shown and credited stays in USD). The payer
 * also covers Paystack's transaction fee (`services.paystack.transaction_fee_percent`)
 * on top, so the amount credited to the shipment is what reaches the account. Every
 * checkout is recorded as a {@see ShipmentPaymentAttempt} first, so whichever of
 * the callback, the webhook or a manual verify arrives first can check Paystack's
 * answer against what was actually asked for.
 */
class ShipmentPaymentService
{
    private const FALLBACK_RATE = 12.0;

    /**
     * Start a checkout and return the attempt; redirect the payer to its
     * `authorizationUrl`.
     *
     * @throws \RuntimeException when Paystack refuses to start the checkout
     */
    public function start(
        Shipment $shipment,
        float $amountUsd,
        string $callbackUrl,
        string $source,
        ?string $email = null,
        ?string $payerPhone = null,
    ): ShipmentPaymentAttempt {
        $amountUsd = round($amountUsd, 2);
        $charge = $this->chargeFor($amountUsd);
        $currency = config('services.paystack.shipment_currency', 'USD');
        $rate = $currency === 'USD' ? null : $this->exchangeRateFor($shipment);

        $attempt = ShipmentPaymentAttempt::create([
            'shipment_id' => $shipment->id,
            'reference' => ShipmentPaymentAttempt::generateReference(),
            'source' => $source,
            'amount_usd' => $amountUsd,
            'fee_percent' => $charge['percent'],
            'fee_usd' => $charge['fee'],
            'currency' => $currency,
            'amount_subunit' => (int) round($charge['total'] * ($rate ?? 1) * 100),
            'exchange_rate' => $rate,
            'payer_email' => $email,
            'payer_phone' => $payerPhone,
            'status' => ShipmentPaymentAttemptStatus::Pending,
        ]);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $email ?: ($shipment->client?->email ?: config('mail.from.address')),
                'amount' => $attempt->amount_subunit,
                'currency' => $currency,
                'reference' => $attempt->reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'type' => 'shipment_payment',
                    'shipment_id' => $shipment->id,
                    'amount_usd' => $amountUsd,
                    'transaction_fee_usd' => $charge['fee'],
                    'source' => $source,
                    'reference' => $shipment->shipping_reference,
                    'tracking_number' => $shipment->tracking_number,
                    'fullname' => $shipment->client?->name,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            $message = $response->json('message') ?? 'Failed to initiate Paystack payment.';
            $attempt->update(['status' => ShipmentPaymentAttemptStatus::Failed, 'gateway_response' => $message]);

            throw new \RuntimeException($message);
        }

        $attempt->authorizationUrl = $response->json('data.authorization_url');
        $attempt->accessCode = $response->json('data.access_code');

        return $attempt;
    }

    /**
     * What a payer is charged to have `$amountUsd` credited to a shipment. The total is
     * grossed up so that after Paystack keeps its percentage the full amount remains:
     * total = amount / (1 - rate), rounded up to the cent.
     *
     * @return array{amount: float, fee: float, total: float, percent: float}
     */
    public function chargeFor(float $amountUsd): array
    {
        $percent = max(0.0, (float) config('services.paystack.transaction_fee_percent', 0));
        $amountCents = (int) round($amountUsd * 100);
        $totalCents = $percent > 0
            ? (int) ceil(round($amountCents / (1 - $percent / 100), 6))
            : $amountCents;

        return [
            'amount' => $amountCents / 100.0,
            'fee' => ($totalCents - $amountCents) / 100.0,
            'total' => $totalCents / 100.0,
            'percent' => $percent,
        ];
    }

    /**
     * The USD→GHS rate a cedi checkout is converted at: today's rate from the daily
     * `app:exchange-rate` sync (or a manually entered one). The service's own
     * built-in default is never used to charge anyone: with no recorded rate, the
     * rate snapshotted on the shipment applies.
     */
    public function exchangeRateFor(Shipment $shipment): float
    {
        $hasRecordedRate = ExchangeRateLog::where('from_currency', 'USD')
            ->where('to_currency', 'GHS')
            ->where('rate_date', '<=', today())
            ->exists();

        $rate = $hasRecordedRate ? app(ExchangeRateService::class)->getCurrentRate('USD', 'GHS') : 0.0;

        return round($rate > 0 ? $rate : (float) ($shipment->exchange_rate_at_shipment ?: self::FALLBACK_RATE), 4);
    }

    /**
     * Start a checkout and return only the Paystack URL — for callers that don't
     * need the attempt.
     */
    public function initialize(Shipment $shipment, float $amountUsd, string $callbackUrl, string $source = 'portal'): string
    {
        return $this->start($shipment, $amountUsd, $callbackUrl, $source)->authorizationUrl;
    }

    /**
     * Verify a Paystack reference and record the payment. Idempotent on the
     * reference and safe to call concurrently (callback and webhook often race).
     * Returns null when the transaction is not a successful, matching shipment payment.
     */
    public function verify(string $reference): ?Payment
    {
        return Cache::lock('shipment-payment:'.$reference, 30)
            ->block(10, fn (): ?Payment => $this->verifyAndRecord($reference));
    }

    /**
     * Re-check a still-pending attempt with Paystack, e.g. when a payer verifies
     * a payment before the webhook has arrived.
     */
    public function refresh(ShipmentPaymentAttempt $attempt): ShipmentPaymentAttempt
    {
        if ($attempt->status === ShipmentPaymentAttemptStatus::Pending) {
            try {
                $this->verify($attempt->reference);
            } catch (\Throwable $e) {
                logger()->warning('Shipment payment refresh failed for '.$attempt->reference.': '.$e->getMessage());
            }
        }

        return $attempt->fresh();
    }

    private function verifyAndRecord(string $reference): ?Payment
    {
        $attempt = ShipmentPaymentAttempt::where('reference', $reference)->first();

        $existing = Payment::where('payment_ref', $reference)->first();
        if ($existing) {
            if ($attempt && ! $attempt->isPaid()) {
                $attempt->update(['status' => ShipmentPaymentAttemptStatus::Success, 'payment_id' => $existing->id]);
            }

            return $existing;
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.rawurlencode($reference));

        $data = $response->json('data') ?? [];
        $status = $data['status'] ?? null;

        if (! $response->successful() || $status !== 'success') {
            // 'abandoned' only means the checkout is still open, so the attempt stays
            // pending; 'failed' and 'reversed' are final.
            if ($attempt && in_array($status, ['failed', 'reversed'], true)) {
                $attempt->update([
                    'status' => ShipmentPaymentAttemptStatus::Failed,
                    'gateway_response' => $data['gateway_response'] ?? $status,
                ]);
            }

            return null;
        }

        $shipment = $attempt?->shipment ?? Shipment::find($data['metadata']['shipment_id'] ?? null);
        if (! $shipment) {
            return null;
        }

        $charged = $this->chargedAmounts($data, $attempt, $shipment);
        if ($charged === null) {
            logger()->error('Shipment payment amount/currency mismatch', [
                'reference' => $reference,
                'paid' => ($data['currency'] ?? '?').' '.($data['amount'] ?? '?'),
                'expected' => $attempt ? $attempt->currency.' '.$attempt->amount_subunit : null,
            ]);
            $attempt?->update(['status' => ShipmentPaymentAttemptStatus::Failed, 'gateway_response' => 'Amount or currency mismatch']);

            return null;
        }

        return DB::transaction(function () use ($shipment, $attempt, $reference, $data, $charged): Payment {
            $payment = Payment::create([
                'branch_id' => $shipment->branch_id,
                'shipment_id' => $shipment->id,
                'payment_ref' => $reference,
                'payment_type' => 'credit',
                'currency' => 'USD',
                'amount' => $charged['usd'],
                'amount_usd' => $charged['usd'],
                'amount_ghs' => $charged['ghs'],
                'exchange_rate' => $charged['rate'],
                'paying_method' => $data['channel'] ?? 'paystack',
                'description' => 'Paystack '.$charged['currency'].' payment'
                    .($attempt ? ' ('.str_replace('_', ' ', $attempt->source).')' : '')
                    .($attempt && (float) $attempt->fee_usd > 0 ? '; transaction charge USD '.number_format((float) $attempt->fee_usd, 2).' paid by the payer' : ''),
                'paid_on' => $data['paid_at'] ?? now(),
                'balance' => max(0, (float) $shipment->total - ($shipment->amount_paid + $charged['usd'])),
            ]);

            $attempt?->update([
                'status' => ShipmentPaymentAttemptStatus::Success,
                'payment_id' => $payment->id,
                'channel' => $data['channel'] ?? null,
                'gateway_response' => $data['gateway_response'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $shipment->recalculatePaymentStatus();

            return $payment;
        });
    }

    /**
     * What was actually charged, in USD (and GHS when charged in cedis). With an
     * attempt on file the charge must match it exactly; references started before
     * attempts existed are valued from the charged amount itself, never from metadata.
     *
     * @param  array<string, mixed>  $data
     * @return array{usd: float, ghs: ?float, rate: ?float, currency: string}|null
     */
    private function chargedAmounts(array $data, ?ShipmentPaymentAttempt $attempt, Shipment $shipment): ?array
    {
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $subunit = (int) ($data['amount'] ?? 0);

        if ($attempt) {
            if ($currency !== $attempt->currency || $subunit < $attempt->amount_subunit) {
                return null;
            }

            $rate = $attempt->exchange_rate !== null ? (float) $attempt->exchange_rate : null;

            return [
                'usd' => (float) $attempt->amount_usd,
                'ghs' => $currency === 'GHS' ? round($subunit / 100, 2) : null,
                'rate' => $rate,
                'currency' => $currency,
            ];
        }

        if ($currency === 'USD') {
            return ['usd' => round($subunit / 100, 2), 'ghs' => null, 'rate' => null, 'currency' => 'USD'];
        }

        if ($currency === 'GHS') {
            $rate = (float) ($shipment->exchange_rate_at_shipment ?: self::FALLBACK_RATE);
            $ghs = round($subunit / 100, 2);

            return ['usd' => round($ghs / $rate, 2), 'ghs' => $ghs, 'rate' => $rate, 'currency' => 'GHS'];
        }

        return null;
    }
}
