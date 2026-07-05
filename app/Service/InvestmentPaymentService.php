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
     * Mirrors EcommercePaymentService::initiatePaystack().
     */
    public function initiatePaystack(Investment $investment, string $email): array
    {
        $amountPesewas = (int) round((float) $investment->principal_amount_ghs * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transaction/initialize', [
                'email' => $email,
                'amount' => $amountPesewas,
                'currency' => 'GHS',
                'metadata' => [
                    'type' => 'investment_deposit',
                    'investment_id' => $investment->id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to initiate Paystack payment.');
        }

        $investment->update(['deposit_gateway' => 'paystack']);

        return [
            'gateway' => 'paystack',
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $response->json('data.reference'),
            'access_code' => $response->json('data.access_code'),
        ];
    }

    /**
     * Initiate a Stripe PaymentIntent for the investment's USD principal.
     * Mirrors EcommercePaymentService::initiateStripe().
     */
    public function initiateStripe(Investment $investment, string $email): array
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));

        $amountCents = (int) round((float) $investment->principal_amount * 100);

        $intent = $stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => 'usd',
            'receipt_email' => $email,
            'metadata' => [
                'type' => 'investment_deposit',
                'investment_id' => $investment->id,
            ],
        ]);

        $investment->update(['deposit_gateway' => 'stripe']);

        return [
            'gateway' => 'stripe',
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
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

    public function verifyAndRecordStripe(string $paymentIntentId): Investment
    {
        $stripe = new StripeClient(config('services.stripe.secret_key'));
        $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            throw new \RuntimeException('Stripe payment not yet succeeded.');
        }

        $investmentId = $intent->metadata['investment_id'] ?? null;
        $investment = Investment::findOrFail($investmentId);

        if ($investment->status === InvestmentStatus::active) {
            return $investment;
        }

        return $this->markPaid($investment, 'stripe', $paymentIntentId);
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
