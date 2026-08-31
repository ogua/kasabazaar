<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;

/**
 * The one Paystack integration for shipment payments: initialise a checkout for
 * any amount up to the outstanding balance, and verify + record the result.
 *
 * Shipment balances are USD-denominated (`payments.amount` is always USD); the
 * amount is converted to GHS at the shipment's snapshot rate for the charge.
 */
class ShipmentPaymentService
{
    private const FALLBACK_RATE = 12.0;

    public function initialize(Shipment $shipment, float $amountUsd, string $callbackUrl): string
    {
        $rate = (float) ($shipment->exchange_rate_at_shipment ?: self::FALLBACK_RATE);
        $amountPesewas = (int) round($amountUsd * $rate * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $shipment->client?->email ?: config('mail.from.address'),
                'amount' => $amountPesewas,
                'currency' => 'GHS',
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'type' => 'shipment_payment',
                    'shipment_id' => $shipment->id,
                    'amount_usd' => round($amountUsd, 2),
                    'public_token' => $shipment->public_view_token,
                    'reference' => $shipment->shipping_reference,
                    'fullname' => $shipment->client?->name,
                    'phone' => $shipment->client?->phone,
                    'email' => $shipment->client?->email,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to initiate Paystack payment.');
        }

        return $response->json('data.authorization_url');
    }

    /**
     * Verify a Paystack reference and record the payment. Idempotent on the
     * reference. Returns null when the transaction is not a successful shipment
     * payment.
     */
    public function verify(string $reference): ?Payment
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            return null;
        }

        $data = $response->json('data');
        $metadata = $data['metadata'] ?? [];
        $shipmentId = $metadata['shipment_id'] ?? null;

        $shipment = $shipmentId ? Shipment::find($shipmentId) : null;
        if (! $shipment) {
            return null;
        }

        $existing = Payment::where('payment_ref', $reference)->first();
        if ($existing) {
            return $existing;
        }

        $rate = (float) ($shipment->exchange_rate_at_shipment ?: self::FALLBACK_RATE);
        $amountUsd = isset($metadata['amount_usd'])
            ? (float) $metadata['amount_usd']
            : round(((int) $data['amount'] / 100) / $rate, 2);

        $payment = Payment::create([
            'branch_id' => $shipment->branch_id,
            'shipment_id' => $shipment->id,
            'payment_ref' => $reference,
            'payment_type' => 'credit',
            'currency' => 'USD',
            'amount' => $amountUsd,
            'amount_usd' => $amountUsd,
            'paying_method' => $data['channel'] ?? 'paystack',
            'paid_on' => $data['paid_at'] ?? now(),
            'balance' => max(0, (float) $shipment->total - ($shipment->amount_paid + $amountUsd)),
        ]);

        $shipment->recalculatePaymentStatus();

        return $payment;
    }
}
