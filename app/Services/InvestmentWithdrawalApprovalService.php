<?php

namespace App\Services;

use App\Enums\InvestmentCapitalType;
use App\Enums\InvestmentInterestPayoutStatus;
use App\Enums\InvestmentStatus;
use App\Enums\InvestmentTransactionType;
use App\Enums\InvestmentWithdrawalStatus;
use App\Models\Investment;
use App\Models\InvestmentTransaction;
use App\Models\InvestmentWithdrawalRequest;
use App\Models\User;
use App\Notifications\InvestmentWithdrawalStatusUpdated;
use App\Service\InvestmentInterestPayoutService;
use App\Service\InvestmentInterestService;
use App\Service\InvestorNotifier;
use Illuminate\Support\Facades\DB;

class InvestmentWithdrawalApprovalService
{
    public function submit(Investment $investment, array $data): InvestmentWithdrawalRequest
    {
        if ($investment->capital_type === InvestmentCapitalType::loan) {
            throw new \InvalidArgumentException(
                'Loans are not eligible for early withdrawal. Principal is due in full at maturity per your Loan Agreement; contact the Company regarding prepayment.'
            );
        }

        if (! $investment->isContractDue()) {
            $maturityDate = $investment->maturity_date?->format('F j, Y');

            throw new \InvalidArgumentException(
                "This investment's contract is not yet due. Withdrawal requests can be submitted starting {$maturityDate}."
            );
        }

        $isFull = (bool) ($data['is_full_withdrawal'] ?? false);
        $requestedAmount = $isFull ? null : (float) ($data['requested_amount'] ?? 0);
        $remainingAfter = null;

        if (! $isFull) {
            $partialMinimum = config('investment.partial_minimum');

            if ($requestedAmount < $partialMinimum) {
                throw new \InvalidArgumentException('Partial withdrawals must be at least $'.number_format($partialMinimum, 2).'.');
            }

            $remainingAfter = round((float) $investment->current_balance - $requestedAmount, 2);
            $minRemaining = config('investment.minimum_remaining_balance');

            if ($remainingAfter < $minRemaining && ! ($data['exception_approved'] ?? false)) {
                throw new \InvalidArgumentException(
                    'Remaining balance would fall below $'.number_format($minRemaining, 2).'. Requires a staff exception.'
                );
            }
        }

        $noticeDate = now();

        return InvestmentWithdrawalRequest::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'is_full_withdrawal' => $isFull,
            'requested_amount' => $requestedAmount,
            'notice_date' => $noticeDate,
            'required_action_by' => $noticeDate->copy()->addDays(config('investment.notice_days')),
            'status' => InvestmentWithdrawalStatus::submitted->value,
            'remaining_balance_after' => $isFull ? 0 : $remainingAfter,
            'exception_approved' => (bool) ($data['exception_approved'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Open the principal repayment for a matured loan as a full withdrawal request in
     * the staff queue, so the lender is repaid when the term ends rather than only if
     * someone remembers. Company-initiated, so it bypasses submit()'s refusal of
     * lender-initiated loan requests. Returns null when the loan has not matured or a
     * request that has not been rejected already exists.
     */
    public function requestLoanPrincipalRepayment(Investment $investment): ?InvestmentWithdrawalRequest
    {
        if ($investment->capital_type !== InvestmentCapitalType::loan
            || $investment->status !== InvestmentStatus::active
            || ! $investment->isContractDue()) {
            return null;
        }

        $hasOpenRequest = $investment->withdrawalRequests()
            ->where('status', '!=', InvestmentWithdrawalStatus::rejected->value)
            ->exists();

        if ($hasOpenRequest) {
            return null;
        }

        return InvestmentWithdrawalRequest::create([
            'investment_id' => $investment->id,
            'investor_id' => $investment->investor_id,
            'is_full_withdrawal' => true,
            'requested_amount' => null,
            'notice_date' => now(),
            'required_action_by' => now(),
            'status' => InvestmentWithdrawalStatus::submitted->value,
            'remaining_balance_after' => 0,
            'exception_approved' => false,
            'notes' => "Loan matured on {$investment->maturity_date->format('F j, Y')} — principal repayment due to the lender.",
        ]);
    }

    public function hasTransferInFlight(InvestmentWithdrawalRequest $request): bool
    {
        return $request->status === InvestmentWithdrawalStatus::processing
            && $request->payout_gateway === 'paystack'
            && filled($request->paystack_transfer_code);
    }

    public function markUnderReview(InvestmentWithdrawalRequest $request): void
    {
        $request->update(['status' => InvestmentWithdrawalStatus::under_review->value]);

        InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));
    }

    public function approve(InvestmentWithdrawalRequest $request, User $approvedBy, array $options = []): InvestmentWithdrawalRequest
    {
        $isLoan = $request->investment->capital_type === InvestmentCapitalType::loan;

        // Outside the transaction, so the payouts this generates survive the refusal
        // and staff can pay them.
        if ($isLoan) {
            $this->assertLoanInterestSettled($request->investment);
        }

        return DB::transaction(function () use ($request, $approvedBy, $options, $isLoan) {
            $deferralDays = (int) ($options['deferral_days'] ?? 0);
            $maxDeferral = config('investment.max_deferral_days');

            if ($deferralDays > $maxDeferral) {
                throw new \InvalidArgumentException("Deferral cannot exceed {$maxDeferral} days.");
            }

            if ($deferralDays > 0 && blank($options['deferral_reason'] ?? null)) {
                throw new \InvalidArgumentException('A deferral reason is required when deferring payment.');
            }

            // Loan principal is owed on the maturity date itself — the 60-day withdrawal
            // window would put the Company in default under the loan agreement.
            $paymentWindow = $isLoan ? 0 : config('investment.payment_window_days');

            $request->update([
                'status' => InvestmentWithdrawalStatus::approved->value,
                'exception_approved' => $options['exception_approved'] ?? $request->exception_approved,
                'deferral_days' => $deferralDays ?: null,
                'deferral_reason' => $options['deferral_reason'] ?? null,
                'payment_due_by' => now()->addDays($paymentWindow + $deferralDays),
                'reviewed_by' => $approvedBy->id,
                'reviewed_at' => now(),
            ]);

            InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));

            return $request->fresh();
        });
    }

    /**
     * The loan agreement requires accrued interest to be paid with the principal, so a
     * principal repayment cannot be approved while any period is unpaid. Generates
     * any period that has fallen due first, so the final quarter is never missed.
     */
    protected function assertLoanInterestSettled(Investment $investment): void
    {
        if ($investment->payout_frequency) {
            app(InvestmentInterestPayoutService::class)->generateDuePayouts($investment, now()->startOfDay());
        }

        $unsettled = $investment->interestPayouts()
            ->whereIn('status', [InvestmentInterestPayoutStatus::due->value, InvestmentInterestPayoutStatus::processing->value])
            ->get();

        if ($unsettled->isNotEmpty()) {
            throw new \InvalidArgumentException(sprintf(
                'This loan still has %d unpaid interest payout(s) totalling $%s. Pay or skip them under Interest Payouts before approving the principal repayment.',
                $unsettled->count(),
                number_format($unsettled->sum(fn ($payout) => (float) $payout->amount - (float) $payout->amount_paid), 2)
            ));
        }
    }

    public function reject(InvestmentWithdrawalRequest $request, User $reviewedBy, string $reason): void
    {
        $request->update([
            'status' => InvestmentWithdrawalStatus::rejected->value,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewedBy->id,
            'reviewed_at' => now(),
        ]);

        InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));
    }

    /**
     * Record an actual payout (manual or gateway-confirmed) against an approved request.
     * On the payment that closes out a full withdrawal, true-ups accrued interest through
     * today first — the resulting fresh current_balance is the authoritative payout amount,
     * regardless of what the caller passed, since any suggested figure was necessarily
     * computed before this true-up ran.
     */
    public function recordPayment(InvestmentWithdrawalRequest $request, ?float $amount, User $recordedBy, array $paymentData = []): InvestmentWithdrawalRequest
    {
        if (! in_array($request->status, [InvestmentWithdrawalStatus::approved, InvestmentWithdrawalStatus::processing], true)) {
            throw new \InvalidArgumentException("Only an approved or processing request can be paid; this one is {$request->status->value}.");
        }

        // While a Paystack transfer is in flight only its own confirmation may record
        // it — a manual entry as well would pay the same money twice.
        if ($this->hasTransferInFlight($request) && ($paymentData['payment_reference'] ?? null) !== $request->paystack_transfer_code) {
            throw new \InvalidArgumentException('A Paystack transfer for this request is still in progress. Wait for it to be confirmed or fail before recording a payment.');
        }

        if (! $request->is_full_withdrawal) {
            $outstanding = round((float) $request->requested_amount - (float) $request->amount_paid, 2);
            $amount = round($amount ?? $outstanding, 2);

            if ($amount <= 0 || $amount > $outstanding + 0.01 || $amount > (float) $request->investment->current_balance + 0.01) {
                throw new \InvalidArgumentException('The amount must be more than $0 and no more than the $'.number_format($outstanding, 2).' still owed on this request.');
            }
        }

        return DB::transaction(function () use ($request, $amount, $recordedBy, $paymentData) {
            $investment = $request->investment;

            if ($request->is_full_withdrawal) {
                // A loan's balance is its flat principal; its interest is settled
                // separately through its interest payouts.
                if ($investment->capital_type !== InvestmentCapitalType::loan) {
                    app(InvestmentInterestService::class)->postStubPeriodInterest($investment, now(), $recordedBy);
                }

                $investment->refresh();
                $amount = (float) $investment->current_balance;
            }

            InvestmentTransaction::create([
                'investment_id' => $investment->id,
                'investor_id' => $investment->investor_id,
                'date' => now(),
                'type' => InvestmentTransactionType::withdrawal->value,
                'op_balance' => $investment->current_balance,
                'debit' => $amount,
                'posted' => true,
                'posted_at' => now(),
                'reference_id' => $request->id,
                'description' => "Withdrawal payout for request {$request->id}",
            ]);

            $newBalance = round((float) $investment->current_balance - $amount, 2);
            $investment->update(['current_balance' => $newBalance]);

            $newAmountPaid = round((float) $request->amount_paid + $amount, 2);
            $isFullyPaid = $request->is_full_withdrawal
                ? $newBalance <= 0.01
                : $newAmountPaid >= (float) $request->requested_amount - 0.01;

            $request->update([
                'amount_paid' => $newAmountPaid,
                'paid_at' => $isFullyPaid ? now() : $request->paid_at,
                'status' => $isFullyPaid ? InvestmentWithdrawalStatus::paid->value : InvestmentWithdrawalStatus::processing->value,
                'payout_gateway' => $paymentData['payout_gateway'] ?? $request->payout_gateway ?? 'manual',
                'payment_method' => $paymentData['payment_method'] ?? $request->payment_method,
                'payment_reference' => $paymentData['payment_reference'] ?? $request->payment_reference,
                'receipt_path' => $paymentData['receipt_path'] ?? $request->receipt_path,
            ]);

            if ($isFullyPaid && $request->is_full_withdrawal) {
                $investment->update(['status' => InvestmentStatus::withdrawn->value]);
            }

            InvestorNotifier::notify($request->investor_id, new InvestmentWithdrawalStatusUpdated($request->fresh()));

            return $request->fresh();
        });
    }
}
