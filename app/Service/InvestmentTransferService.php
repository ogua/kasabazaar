<?php

namespace App\Service;

use App\Enums\InvestmentWithdrawalStatus;
use App\Models\InvestmentWithdrawalRequest;
use App\Models\Investor;
use App\Models\User;
use App\Services\InvestmentWithdrawalApprovalService;
use Illuminate\Support\Facades\Http;

class InvestmentTransferService
{
    public function __construct(protected InvestmentInterestService $interestService) {}

    /**
     * Create (or reuse the cached) Paystack transfer recipient for an investor's bank details.
     */
    public function ensureRecipient(Investor $investor): string
    {
        if ($investor->paystack_recipient_code) {
            return $investor->paystack_recipient_code;
        }

        if (! $investor->bank_code || ! $investor->account_number) {
            throw new \RuntimeException('Investor is missing bank details required for a Paystack transfer.');
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transferrecipient', [
                'type' => 'nuban',
                'name' => $investor->account_name ?? $investor->name,
                'account_number' => $investor->account_number,
                'bank_code' => $investor->bank_code,
                'currency' => 'GHS',
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to create Paystack transfer recipient.');
        }

        $recipientCode = $response->json('data.recipient_code');
        $investor->update(['paystack_recipient_code' => $recipientCode]);

        return $recipientCode;
    }

    /**
     * Initiate a Paystack transfer for an approved withdrawal request. For a full
     * withdrawal, true-ups accrued interest first so the transferred amount matches
     * the investment's real current value, exactly like the manual recordPayment() path.
     */
    public function initiatePaystackTransfer(InvestmentWithdrawalRequest $request, User $initiatedBy): array
    {
        $investment = $request->investment;

        if ($request->is_full_withdrawal) {
            $this->interestService->postStubPeriodInterest($investment, now(), $initiatedBy);
            $investment->refresh();
            $amount = (float) $investment->current_balance;
        } else {
            $amount = (float) $request->requested_amount - (float) $request->amount_paid;
        }

        $recipientCode = $this->ensureRecipient($request->investor);
        $reference = 'INVWD-'.$request->id.'-'.now()->timestamp;

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post(config('services.paystack.payment_url').'/transfer', [
                'source' => 'balance',
                'amount' => (int) round($amount * 100),
                'recipient' => $recipientCode,
                'reason' => "Investment withdrawal payout — {$investment->reference}",
                'reference' => $reference,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException($response->json('message') ?? 'Failed to initiate Paystack transfer.');
        }

        $request->update([
            'payout_gateway' => 'paystack',
            'status' => InvestmentWithdrawalStatus::processing->value,
            'paystack_transfer_code' => $response->json('data.transfer_code'),
            'payment_reference' => $reference,
        ]);

        return $response->json('data');
    }

    /**
     * Called from the Paystack transfer webhook on `transfer.success`. Idempotent —
     * a request already marked paid is left untouched.
     */
    public function handleTransferSuccess(string $transferCode): void
    {
        $request = InvestmentWithdrawalRequest::where('paystack_transfer_code', $transferCode)->first();

        if (! $request || $request->status === InvestmentWithdrawalStatus::paid) {
            return;
        }

        $amount = $request->is_full_withdrawal
            ? null // recordPayment() derives the authoritative trued-up amount itself
            : (float) $request->requested_amount - (float) $request->amount_paid;

        app(InvestmentWithdrawalApprovalService::class)->recordPayment(
            $request,
            $amount,
            $this->resolveSystemUser($request),
            ['payout_gateway' => 'paystack', 'payment_reference' => $transferCode]
        );
    }

    /**
     * Called on `transfer.failed` / `transfer.reversed` — reverts to 'approved' so
     * staff can retry the transfer or fall back to a manual payout.
     */
    public function handleTransferFailed(string $transferCode): void
    {
        InvestmentWithdrawalRequest::where('paystack_transfer_code', $transferCode)
            ->where('status', InvestmentWithdrawalStatus::processing->value)
            ->first()
            ?->update(['status' => InvestmentWithdrawalStatus::approved->value]);
    }

    private function resolveSystemUser(InvestmentWithdrawalRequest $request): User
    {
        return $request->investment->recordedBy
            ?? User::role('super_admin')->first()
            ?? User::firstOrFail();
    }
}
