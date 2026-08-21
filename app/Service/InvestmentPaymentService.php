<?php

namespace App\Service;

use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;

class InvestmentPaymentService
{
    /**
     * Record a deposit that already arrived outside the app (bank wire, cheque, etc.),
     * mirroring the IncomeResource pattern: payment_method + reference + receipt upload.
     */
    public function recordManualDeposit(Investment $investment, array $data): Investment
    {
        return DB::transaction(function () use ($investment, $data) {
            $investment->update([
                'deposit_gateway' => 'manual',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'receipt_path' => $data['receipt_path'] ?? null,
                'status' => InvestmentStatus::active->value,
            ]);

            $this->createContributionLedgerRow($investment);

            return $investment->fresh();
        });
    }

    /**
     * Initiate a Paystack checkout for the investment's GHS-equivalent principal.
     * $callbackUrl is only meaningful for a browser-based caller (Filament) whose
     * session survives the redirect round-trip — the mobile app ignores it and
     * verifies explicitly using the returned reference once its in-app browser closes.
     */
    public function initiatePaystack(Investment $investment, string $email, ?string $callbackUrl = null): array
    {
        $amountPesewas = (int) round((float) $investment->principal_amount_ghs * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', array_filter([
                'email' => $email,
                'amount' => $amountPesewas,
                'currency' => 'GHS',
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'type' => 'investment_deposit',
                    'investment_id' => $investment->id,
                ],
            ]));

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to initiate Paystack payment.');
        }

        $investment->update(['deposit_gateway' => 'paystack']);

        return [
            'gateway' => 'paystack',
            'url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
        ];
    }

    /**
     * Initiate a hosted Stripe Checkout Session for the investment's USD principal.
     * Uses Checkout (not a raw PaymentIntent) so both Filament and the mobile app
     * can complete payment via a plain browser redirect with no Stripe SDK needed.
     */
    public function initiateStripe(Investment $investment, string $email, string $successUrl, string $cancelUrl): array
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));

        $amountCents = (int) round((float) $investment->principal_amount * 100);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => "Investment — {$investment->reference}",
                    ],
                ],
            ]],
            'metadata' => [
                'type' => 'investment_deposit',
                'investment_id' => $investment->id,
            ],
        ]);

        $investment->update(['deposit_gateway' => 'stripe']);

        return [
            'gateway' => 'stripe',
            'url' => $session->url,
            'reference' => $session->id,
        ];
    }

    public function verifyAndRecordPaystack(string $reference): Investment
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            throw new \RuntimeException('Paystack payment verification failed.');
        }

        $metadata = $response->json('data.metadata') ?? [];
        $investmentId = $metadata['investment_id'] ?? null;

        $investment = Investment::findOrFail($investmentId);

        if ($investment->status === InvestmentStatus::active) {
            return $investment; // already processed (idempotency)
        }

        return $this->markPaid($investment, 'paystack', $reference);
    }

    public function verifyAndRecordStripe(string $sessionId): Investment
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            throw new \RuntimeException('Stripe payment not yet completed.');
        }

        $investmentId = $session->metadata['investment_id'] ?? null;
        $investment = Investment::findOrFail($investmentId);

        if ($investment->status === InvestmentStatus::active) {
            return $investment;
        }

        return $this->markPaid($investment, 'stripe', $sessionId);
    }

    private function markPaid(Investment $investment, string $gateway, string $reference): Investment
    {
        return DB::transaction(function () use ($investment, $gateway, $reference) {
            $investment->update([
                'deposit_gateway' => $gateway,
                'payment_reference' => $reference,
                'status' => InvestmentStatus::active->value,
            ]);

            $this->createContributionLedgerRow($investment);

            return $investment->fresh();
        });
    }

    /**
     * Activate a tranche whose "deposit" is capital rolled over from one or more
     * settled tranches rather than new money arriving. Kept on the same activation
     * path as markPaid()/recordManualDeposit() so every tranche in the system goes
     * active and opens its ledger the same way — the only differences are that no
     * gateway or receipt is involved, and the opening row is typed conversion_in
     * rather than contribution so a statement can pair it with the matching
     * conversion_out on the source.
     */
    public function recordConversionDeposit(Investment $investment, string $conversionId, string $conversionReference): Investment
    {
        return DB::transaction(function () use ($investment, $conversionId, $conversionReference) {
            $investment->update([
                'deposit_gateway' => 'conversion',
                'payment_reference' => $conversionReference,
                'status' => InvestmentStatus::active->value,
            ]);

            if (! $investment->transactions()->where('type', InvestmentTransactionType::conversion_in->value)->exists()) {
                InvestmentTransaction::create([
                    'investment_id' => $investment->id,
                    'investor_id' => $investment->investor_id,
                    'date' => $investment->start_date,
                    'type' => InvestmentTransactionType::conversion_in->value,
                    'op_balance' => 0,
                    'credit' => $investment->principal_amount,
                    'posted' => true,
                    'posted_at' => now(),
                    'reference_id' => $conversionId,
                    'description' => "Capital converted in under {$conversionReference}.",
                ]);
            }

            return $investment->fresh();
        });
    }

    private function createContributionLedgerRow(Investment $investment): void
    {
        if ($investment->transactions()->where('type', InvestmentTransactionType::contribution->value)->exists()) {
            return;
        }

        InvestmentTransaction::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'date' => $investment->start_date,
            'type' => InvestmentTransactionType::contribution->value,
            'op_balance' => 0,
            'credit' => $investment->principal_amount,
            'posted' => true,
            'posted_at' => now(),
            'description' => "Initial contribution recorded via {$investment->deposit_gateway}.",
        ]);
    }
}
